<?php

namespace App\Http\Controllers;

use App\Models\GoaliCompany;
use App\Models\GoaliPassenger;
use App\Models\GoaliTrip;
use App\Models\Spatie\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GoaliTripController extends Controller
{
    private const PERMISSION_VIEW_TRIPS = 'goali-visualizar-corridas';
    private const PERMISSION_VIEW_ALL_TRIPS = 'goali-visualizar-todas-corridas';
    private const PERMISSION_CREATE_TRIPS = 'goali-criar-corridas';
    private const PERMISSION_CREATE_FOR_DRIVERS = 'goali-criar-para-motoristas';
    private const PERMISSION_MANAGE_TRIPS = 'goali-gerenciar-corridas';
    private const PERMISSION_MANAGE_DIRECTORY = 'goali-gerenciar-cadastros';
    private const PERMISSION_UPDATE_STATUS = 'goali-atualizar-status';
    private const PERMISSION_CANCEL_TRIPS = 'goali-cancelar-corridas';
    private const TRIP_STATUSES = ['Criada', 'Em Andamento', 'Aguardando Aprovação', 'Pago', 'Cancelada'];

    public function form()
    {
        $user = Auth::user();
        abort_unless($this->hasPermission($user, self::PERMISSION_CREATE_TRIPS), 403);

        return Inertia::render('Goali/Form', [
            'solicitations' => $user ? $this->solicitationOptionsForUser($user) : [],
            'responsibleUser' => $user?->name ?? '',
            'initialTripStatus' => $user && ($this->isAdmin($user) || $this->isDriver($user) || $this->canCreateForDrivers($user))
                ? 'Em Andamento'
                : 'Criada',
            'currentUserId' => $user?->id,
            'drivers' => $user ? $this->tripDriverOptionsForCurrentUser($user) : [],
            'companies' => $user ? $this->companiesForCurrentTeam($user) : [],
            'passengers' => $user ? $this->passengersForCurrentTeam($user) : [],
            'initialDriverId' => $user && collect($this->tripDriverOptionsForCurrentUser($user))->contains('id', $user->id)
                ? $user->id
                : null,
            'isDriver' => $user ? $this->isDriver($user) : false,
            'isAdmin' => $user ? $this->isAdmin($user) : false,
            'isAdministrative' => $user ? $this->isAdministrative($user) : false,
            'canAssignDrivers' => $user ? $this->canCreateForDrivers($user) || $this->isAdmin($user) : false,
            'canManageDirectory' => $user ? $this->hasPermission($user, self::PERMISSION_MANAGE_DIRECTORY) : false,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($this->hasPermission($user, self::PERMISSION_CREATE_TRIPS), 403);
        $rangeEndDate = null;

        if ($request->exists('travel_start_date') || $request->exists('travel_end_date')) {
            $dateRange = $request->validate([
                'travel_start_date' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:' . $this->earliestCreatableDate($user),
                ],
                'travel_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:travel_start_date'],
                'travel_time' => ['required', 'date_format:H:i'],
            ]);

            $rangeEndDate = $dateRange['travel_end_date'];
            $request->merge(['travel_date' => $dateRange['travel_start_date']]);
        }

        if ($this->isDriver($user)) {
            $request->merge(['driver_id' => $user->getAuthIdentifier()]);
        }

        $validated = $this->validatedTrip($request, $user, true);
        $validated = $this->applyDirectoryValues($validated, $user);
        $validated['user_id'] = $user->getAuthIdentifier();
        $validated['responsible'] = $user->name;
        $createdForAnotherDriver = ($this->isAdmin($user) || $this->canCreateForDrivers($user))
            && ! empty($validated['driver_id'])
            && (int) $validated['driver_id'] !== (int) $user->getAuthIdentifier();
        $validated['trip_status'] = $createdForAnotherDriver
            ? 'Criada'
            : (($this->isAdmin($user) || $this->isDriver($user) || $this->canCreateForDrivers($user) || empty($validated['driver_id']))
                ? 'Em Andamento'
                : 'Criada');
        $validated['in_progress_started_at'] = $validated['trip_status'] === 'Em Andamento' ? now() : null;

        DB::transaction(function () use ($validated, $rangeEndDate) {
            $date = CarbonImmutable::parse($validated['travel_date']);
            $endDate = CarbonImmutable::parse($rangeEndDate ?? $validated['travel_date']);

            for (; $date->lessThanOrEqualTo($endDate); $date = $date->addDay()) {
                $tripData = [
                    ...$validated,
                    'travel_date' => $date->toDateString(),
                ];
                GoaliTrip::create($tripData);
            }
        });
        return redirect()->route('goali.index')->with(
            'success',
            $rangeEndDate
                ? 'Corridas criadas com sucesso para todas as datas do período.'
                : 'Registro enviado com sucesso.'
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($this->hasPermission($user, self::PERMISSION_VIEW_TRIPS), 403);
        $filters = $this->validatedFilters($request);
        $periodRecords = $this->recordsForDateRange($filters, $user);
        $records = $this->filterRecords($periodRecords, $filters, $user);
        $isAdmin = $this->isAdmin($user);
        $records->each(function (GoaliTrip $record) use ($isAdmin): void {
            if (! $isAdmin) {
                $record->makeHidden('company_profit');
            }

            if (! $record->driver) {
                return;
            }

            $record->driver->append('profile_photo_url');

            if ($record->driver->profile_photo_path && Storage::disk('public')->exists($record->driver->profile_photo_path)) {
                $record->driver->setAttribute(
                    'profile_photo_url',
                    '/storage/' . ltrim($record->driver->profile_photo_path, '/'),
                );
            } else {
                $record->driver->setAttribute('profile_photo_url', null);
            }
        });

        $summary = [
            'count' => $records->count(),
            'amount' => (float) $records->sum(fn(GoaliTrip $record) => $record->driver_id !== null ? (float) $record->amount : 0),
            'receivable' => (float) $records->sum(fn(GoaliTrip $record) => $record->trip_status === 'Pago' && $record->driver_id !== null ? (float) $record->amount : 0),
        ];

        if ($isAdmin) {
            $summary['companyProfit'] = (float) $records->sum(fn(GoaliTrip $record) => (float) ($record->company_profit ?? 0));
        }

        return Inertia::render('Goali/Index', [
            'records' => $records,
            'summary' => $summary,
            'filters' => $filters,
            'isAdmin' => $this->isAdmin($user),
            'canViewAllTrips' => $this->canViewAllTrips($user) || $isAdmin,
            'isAdministrative' => $this->isAdministrative($user),
            'isDriver' => $this->isDriver($user),
            'currentDriverId' => $this->isDriver($user) ? $user->id : null,
            'canCreateTrips' => $this->hasPermission($user, self::PERMISSION_CREATE_TRIPS),
            'canCancelTrips' => $this->hasPermission($user, self::PERMISSION_CANCEL_TRIPS),
            'solicitations' => $this->solicitationOptionsForUser($user),
            'passengers' => $this->passengerOptionsForRecords($periodRecords),
            'responsibles' => ($this->canViewAllTrips($user) || $isAdmin)
                ? $periodRecords->pluck('responsible')->filter()->unique()->sort()->values()->all()
                : [],
            'tripStatuses' => self::TRIP_STATUSES,
            'tripRoles' => $isAdmin ? $this->tripRoleOptionsForCurrentTeam($user) : [],
            'drivers' => $this->filterDriversForCurrentUser($user),
            'filterDrivers' => $this->filterDriversForCurrentUser($user),
        ]);
    }

    public function update(Request $request, GoaliTrip $trip)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);
        $isAdministrative = $this->isAdministrative($user);
        $isDriver = $this->isDriver($user);
        $isCancelling = $request->boolean('cancel_trip');
        $isMarkingCancelled = $request->boolean('mark_cancelled');
        abort_unless($isAdmin || $isAdministrative || $isDriver || ($isCancelling && $this->hasPermission($user, self::PERMISSION_CANCEL_TRIPS)), 403);

        if ($isMarkingCancelled) {
            abort_unless($isAdmin, 403);
            $request->validate(['mark_cancelled' => ['required', 'boolean']]);
            DB::transaction(function () use ($user, $trip) {
                $lockedTrip = $this->queryForUser($user)
                    ->lockForUpdate()
                    ->findOrFail($trip->getKey());

                abort_unless($lockedTrip->trip_status !== 'Cancelada', 403);

                $lockedTrip->update([
                    'trip_status' => 'Cancelada',
                    'in_progress_started_at' => null,
                    'cancelled_by_name' => $user->name,
                    'cancelled_by_role' => 'Admin',
                    'cancelled_at' => now(),
                ]);
            });

            return back()->with('success', 'Corrida marcada como cancelada e mantida no histórico.');
        }

        if ($isCancelling) {
            abort_unless($this->hasPermission($user, self::PERMISSION_CANCEL_TRIPS), 403);
            $request->validate(['cancel_trip' => ['required', 'boolean']]);
            DB::transaction(function () use ($user, $isAdmin, $trip) {
                $lockedTrip = $this->queryForUser($user)
                    ->lockForUpdate()
                    ->findOrFail($trip->getKey());

                abort_unless(
                    $lockedTrip->trip_status !== 'Pago'
                        && $lockedTrip->trip_status !== 'Criada'
                        && $lockedTrip->trip_status !== 'Cancelada'
                        && $lockedTrip->driver_id !== null,
                    403
                );

                $lockedTrip->update([
                    'trip_status' => 'Criada',
                    'driver_id' => null,
                    'in_progress_started_at' => null,
                    'cancelled_by_name' => $user->name,
                    'cancelled_by_role' => $isAdmin ? 'Admin' : (string) $user->getRoleByUser(),
                    'cancelled_at' => now(),
                ]);
            });

            return back()->with('success', 'Corrida cancelada e devolvida às corridas pendentes.');
        }

        if ($isDriver) {
            $validated = $request->validate([
                'trip_status' => ['required', Rule::in(['Em Andamento', 'Aguardando Aprovação'])],
            ]);

            DB::transaction(function () use ($user, $trip, $validated) {
                $lockedTrip = $this->queryForUser($user)
                    ->lockForUpdate()
                    ->findOrFail($trip->getKey());

                if ($lockedTrip->trip_status === 'Criada') {
                    abort_unless(
                        ($lockedTrip->driver_id === null
                            || (int) $lockedTrip->driver_id === (int) $user->id)
                            && $validated['trip_status'] === 'Em Andamento',
                        403
                    );

                    $lockedTrip->update([
                        'driver_id' => $user->id,
                        'trip_status' => 'Em Andamento',
                        'in_progress_started_at' => now(),
                        'cancelled_by_name' => null,
                        'cancelled_by_role' => null,
                        'cancelled_at' => null,
                    ]);
                    return;
                }

                abort_unless(
                    $lockedTrip->trip_status === 'Em Andamento'
                        && $validated['trip_status'] === 'Aguardando Aprovação',
                    403
                );
                abort_unless(
                    $lockedTrip->driver_id === null || (int) $lockedTrip->driver_id === (int) $user->id,
                    403
                );

                $lockedTrip->update([
                    'driver_id' => $user->id,
                    'trip_status' => 'Aguardando Aprovação',
                    'cancelled_by_name' => null,
                    'cancelled_by_role' => null,
                    'cancelled_at' => null,
                ]);
            });

            return back()->with(
                'success',
                $validated['trip_status'] === 'Em Andamento'
                    ? 'Corrida aceita com sucesso.'
                    : 'Corrida confirmada e enviada para aprovação.'
            );
        }

        if ($isAdministrative) {
            $validated = $request->validate([
                'trip_status' => ['required', Rule::in(['Em Andamento', 'Aguardando Aprovação'])],
            ]);
            $trip = $this->queryForUser($user)->findOrFail($trip->getKey());
            abort_unless(
                ($trip->trip_status === 'Criada' && $validated['trip_status'] === 'Em Andamento')
                    || ($trip->trip_status === 'Em Andamento' && $validated['trip_status'] === 'Aguardando Aprovação'),
                403
            );
            $trip->update([
                'trip_status' => $validated['trip_status'],
                'in_progress_started_at' => $validated['trip_status'] === 'Em Andamento'
                    ? now()
                    : $trip->in_progress_started_at,
                'cancelled_by_name' => null,
                'cancelled_by_role' => null,
                'cancelled_at' => null,
            ]);

            return back()->with('success', 'Status da corrida atualizado com sucesso.');
        }

        $trip = $this->queryForUser($user)->findOrFail($trip->getKey());
        abort_if($trip->trip_status === 'Pago', 403, 'Corridas pagas não podem ser editadas.');

        $validated = $this->validatedTrip($request, $user, false, true);
        $validated = $this->applyDirectoryValues($validated, $user);
        $nextStatus = match ($trip->trip_status) {
            'Criada' => 'Em Andamento',
            'Em Andamento' => 'Pago',
            'Aguardando Aprovação' => 'Pago',
            default => null,
        };
        $requestedStatus = $validated['trip_status'] ?? null;

        if ($requestedStatus !== null) {
            abort_unless($requestedStatus === $nextStatus, 403);
            $validated['trip_status'] = $requestedStatus;
            if ($requestedStatus === 'Em Andamento') {
                $validated['in_progress_started_at'] = now();
            }
            $validated['cancelled_by_name'] = null;
            $validated['cancelled_by_role'] = null;
            $validated['cancelled_at'] = null;
        } else {
            unset($validated['trip_status']);
        }

        $trip->update($validated);

        return back()->with('success', 'Registro atualizado com sucesso.');
    }

    public function export(Request $request)
    {
        abort_unless($this->hasPermission($request->user(), self::PERMISSION_VIEW_TRIPS), 403);
        $user = $request->user();
        $filters = $this->validatedFilters($request);
        $records = $this->filterRecords($this->recordsForDateRange($filters, $user), $filters, $user);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('relatorio_goali');
        $headers = ['ID', 'Data', 'Horário', 'Tipo', 'Status da corrida', 'Solicitação', 'Código da Viagem', 'Origem', 'Destino', 'Passageiro', 'Motorista', 'Responsável', 'Valor (R$)', 'Cancelada por'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
        }

        foreach ($records as $rowIndex => $record) {
            $values = [
                'ID' . $record->id,
                $record->travel_date->format('d/m/Y'),
                substr($record->travel_time, 0, 5),
                $record->status,
                $record->trip_status,
                $record->solicitation,
                $record->trip_code ?? '',
                $record->origin,
                $record->destination,
                $record->passenger,
                $record->driver?->name ?? '',
                $record->responsible,
                (float) $record->amount,
                $record->cancelled_by_role
                    ? $record->cancelled_by_role . ': ' . $record->cancelled_by_name
                    : '',
            ];

            foreach ($values as $columnIndex => $value) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex + 1) . ($rowIndex + 2);
                if (is_string($value)) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
                    continue;
                }

                $sheet->setCellValue($cell, $value);
            }
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $tempFile = tempnam(sys_get_temp_dir(), 'goali-report-');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, 'relatorio_goali_' . now()->format('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    private function validatedFilters(Request $request): array
    {
        return [
            ...$this->validatedDateFilters($request),
            ...$this->validatedReportFilters($request, $request->user()),
        ];
    }

    private function validatedDateFilters(Request $request): array
    {
        $dateFilterProvided = $request->query->has('startDate') || $request->query->has('endDate');
        $filters = $request->validate([
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
        ]);

        return array_merge(
            $dateFilterProvided
                ? ['startDate' => '', 'endDate' => '']
                : [
                    'startDate' => now()->startOfMonth()->toDateString(),
                    'endDate' => now()->endOfMonth()->toDateString(),
                ],
            $filters
        );
    }

    private function validatedReportFilters(Request $request, User $user): array
    {
        $rules = [
            'status' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $statuses = is_array($value) ? $value : [$value];
                    foreach ($statuses as $status) {
                        if ($status === '') {
                            continue;
                        }

                        if (! is_string($status) || ! in_array($status, ['ENTRADA', 'SAÍDA'], true)) {
                            $fail('O tipo selecionado é inválido.');
                            return;
                        }
                    }
                },
            ],
            'tripStatus' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $statuses = is_array($value) ? $value : [$value];
                    foreach ($statuses as $status) {
                        if ($status === '') {
                            continue;
                        }

                        if (! is_string($status) || ! in_array($status, self::TRIP_STATUSES, true)) {
                            $fail('O status da corrida selecionado é inválido.');
                            return;
                        }
                    }
                },
            ],
            'solicitation' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $solicitations = is_array($value) ? $value : [$value];
                    foreach ($solicitations as $solicitation) {
                        if ($solicitation === '') {
                            continue;
                        }

                        if (! is_string($solicitation) || strlen($solicitation) > 150) {
                            $fail('A solicitação selecionada é inválida.');
                            return;
                        }
                    }
                },
            ],
            'passenger' => ['nullable', 'string'],
            'sortOrder' => ['nullable', 'in:asc,desc'],
        ];

        if ($this->canViewAllTrips($user) || $this->isAdmin($user)) {
            $rules['driverId'] = [
                'nullable',
                'integer',
                Rule::in($this->filterableDriverIdsForCurrentTeam($user)),
            ];
            $rules['responsible'] = ['nullable', 'string', 'max:255'];
            $roles = $this->tripRoleOptionsForCurrentTeam($user);
            $rules['driverRole'] = ['nullable', 'string', Rule::in($roles)];
            $rules['responsibleRole'] = ['nullable', 'string', Rule::in($roles)];
        }

        $filters = $request->validate($rules);
        foreach (['status', 'tripStatus', 'solicitation'] as $filterName) {
            if (array_key_exists($filterName, $filters)) {
                $values = is_array($filters[$filterName]) ? $filters[$filterName] : [$filters[$filterName]];
                $filters[$filterName] = array_values(array_filter($values, fn($value) => $value !== ''));
            }
        }

        return array_merge([
            'status' => [],
            'tripStatus' => '',
            'solicitation' => [],
            'passenger' => '',
            'responsible' => '',
            'driverId' => '',
            'driverRole' => '',
            'responsibleRole' => '',
            'sortOrder' => 'desc',
        ], $filters);
    }

    private function recordsForDateRange(array $filters, User $user): Collection
    {
        $sortOrder = $filters['sortOrder'] ?? 'desc';

        return $this->queryForUser($user)
            ->when($filters['startDate'] ?? null, fn($query, $date) => $query->whereDate('travel_date', '>=', $date))
            ->when($filters['endDate'] ?? null, fn($query, $date) => $query->whereDate('travel_date', '<=', $date))
            ->with('driver:id,name,profile_photo_path')
            ->orderByRaw("CASE trip_status WHEN 'Criada' THEN 0 WHEN 'Em Andamento' THEN 1 WHEN 'Aguardando Aprovação' THEN 2 WHEN 'Pago' THEN 3 ELSE 4 END")
            ->orderBy('travel_date', $sortOrder)
            ->orderBy('travel_time', $sortOrder)
            ->orderBy('id', $sortOrder)
            ->get();
    }

    private function filterRecords(Collection $records, array $filters, User $user): Collection
    {
        $statuses = array_values(array_filter((array) ($filters['status'] ?? []), fn($value) => $value !== ''));
        $tripStatuses = array_values(array_filter((array) ($filters['tripStatus'] ?? []), fn($value) => $value !== ''));
        $solicitations = array_values(array_filter((array) ($filters['solicitation'] ?? []), fn($value) => $value !== ''));
        $driverId = $filters['driverId'] ?? '';
        $driverRole = $filters['driverRole'] ?? '';
        $responsibleRole = $filters['responsibleRole'] ?? '';
        $passenger = $filters['passenger'] ?? '';
        $responsible = $filters['responsible'] ?? '';
        $driverRoles = [];
        $responsibleNamesByRole = [];

        if ($driverRole !== '' || $responsibleRole !== '') {
            $members = DB::table('team_user')
                ->join('users', 'users.id', '=', 'team_user.user_id')
                ->where('team_user.team_id', $user->current_team_id)
                ->get(['team_user.user_id', 'team_user.role', 'users.name']);

            $driverRoles = $members
                ->mapWithKeys(fn($member) => [(int) $member->user_id => $member->role])
                ->all();
            $responsibleNamesByRole = $members
                ->groupBy('role')
                ->map(fn($roleMembers) => $roleMembers->pluck('name')->all())
                ->all();
        }

        return $records->filter(function (GoaliTrip $record) use ($statuses, $tripStatuses, $solicitations, $driverId, $driverRole, $responsibleRole, $passenger, $responsible, $driverRoles, $responsibleNamesByRole) {
            if ($statuses && ! in_array($record->status, $statuses, true)) {
                return false;
            }

            if ($tripStatuses && ! in_array($record->trip_status, $tripStatuses, true)) {
                return false;
            }

            if ($solicitations && ! in_array($record->solicitation, $solicitations, true)) {
                return false;
            }

            if ($driverId !== '' && (string) $record->driver_id !== (string) $driverId) {
                return false;
            }

            if ($driverRole !== '' && ($driverRoles[(int) $record->driver_id] ?? null) !== $driverRole) {
                return false;
            }

            if ($responsibleRole !== '' && ! in_array($record->responsible, $responsibleNamesByRole[$responsibleRole] ?? [], true)) {
                return false;
            }

            if ($responsible !== '' && $record->responsible !== $responsible) {
                return false;
            }

            return $passenger === '' || in_array($passenger, $this->passengerLines($record), true);
        })->values();
    }
    private function tripRoleOptionsForCurrentTeam(User $user): array
    {
        if (! $user->current_team_id) {
            return [];
        }

        return DB::table('team_user')
            ->where('team_id', $user->current_team_id)
            ->whereNotNull('role')
            ->distinct()
            ->orderBy('role')
            ->pluck('role')
            ->all();
    }
    private function passengerOptionsForRecords(Collection $records): array
    {
        return $records
            ->flatMap(fn(GoaliTrip $record) => $this->passengerLines($record))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function passengerLines(GoaliTrip $record): array
    {
        return collect(preg_split('/\R/u', (string) $record->passenger))
            ->map(fn(string $passenger) => trim($passenger))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function queryForUser(User $user)
    {
        $query = GoaliTrip::query();

        if ($this->isGoaliScopedManager($user) && ! $this->isAdmin($user)) {
            $assignedDriverIds = $this->assignedDriverIdsForCurrentTeam($user);

            $query->where(function ($query) use ($user, $assignedDriverIds) {
                // Corridas em que é o responsável.
                $query->where('responsible', $user->name)

                    // Corridas em que ele próprio é o motorista.
                    ->orWhere('driver_id', $user->id);

                // Corridas dos motoristas vinculados ao administrativo.
                if ($assignedDriverIds) {
                    $query->orWhereIn('driver_id', $assignedDriverIds);
                }
            });
        } elseif (! $this->canViewAllTrips($user) && ! $this->isAdmin($user)) {
            $query->where(function ($query) use ($user) {
                if ($this->isDriver($user)) {
                    $query->where('driver_id', $user->id)
                        ->orWhereNull('driver_id');
                } else {
                    $query->where('responsible', $user->name)
                        ->orWhere('driver_id', $user->id);
                }
            });
        }

        return $query;
    }

    private function isDriver(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_UPDATE_STATUS);
    }

    private function isAdmin(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_MANAGE_TRIPS);
    }

    private function isAdministrative(User $user): bool
    {
        return strcasecmp(trim((string) $user->getRoleByUser()), 'Administrativo') === 0;
    }

    private function canViewAllTrips(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_VIEW_ALL_TRIPS);
    }

    private function canCreateForDrivers(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_CREATE_FOR_DRIVERS);
    }

    private function hasPermission(?User $user, string $permission): bool
    {
        return $user && collect($user->getPermissionsInTeamByUser())->contains($permission);
    }

    private function rules(User $user, bool $includeCompanyProfit = false): array
    {
        $rules = [
            'travel_date' => ['required', 'date'],
            'travel_time' => ['required', 'date_format:H:i'],
            'status' => ['required', 'in:ENTRADA,SAÍDA'],
            'trip_status' => ['sometimes', 'nullable', Rule::in(self::TRIP_STATUSES)],
            'driver_id' => [
                $this->isAdmin($user) || $this->canCreateForDrivers($user) ? 'nullable' : 'required',
                'required_if:trip_status,Pago',
                'integer',
                Rule::in($this->isAdmin($user) || $this->canCreateForDrivers($user)
                    ? array_column($this->tripDriverOptionsForCurrentUser($user), 'id')
                    : [$user->id]),
            ],
            'solicitation' => ['required', 'string', 'max:150'],
            'company_id' => [
                'nullable',
                'integer',
                Rule::exists('goali_companies', 'id')->where(fn($query) => $query->where('team_id', $user->current_team_id)),
            ],
            'passenger_id' => [
                'nullable',
                'integer',
                Rule::exists('goali_passengers', 'id')->where(fn($query) => $query->where('team_id', $user->current_team_id)),
            ],
            'passenger_ids' => ['nullable', 'array'],
            'passenger_ids.*' => [
                'integer',
                Rule::exists('goali_passengers', 'id')->where(fn($query) => $query->where('team_id', $user->current_team_id)),
            ],
            'trip_code' => ['nullable', 'string', 'max:255'],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'passenger' => ['required', 'string'],
            'observation' => ['nullable', 'string', 'max:5000'],
            'responsible' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ];

        if ($includeCompanyProfit && $this->isAdmin($user)) {
            $rules['company_profit'] = ['nullable', 'numeric', 'decimal:0,2', 'min:0'];
        }

        return $rules;
    }

    private function validatedTrip(
        Request $request,
        User $user,
        bool $disallowPastDate = false,
        bool $includeCompanyProfit = false
    ): array {
        if ($request->exists('travel_datetime')) {
            $request->validate([
                'travel_datetime' => ['required', 'date_format:Y-m-d\TH:i'],
            ]);

            [$date, $time] = explode('T', $request->input('travel_datetime'), 2);
            $request->merge([
                'travel_date' => $date,
                'travel_time' => $time,
            ]);
        }

        $rules = $this->rules($user, $includeCompanyProfit);
        if ($disallowPastDate) {
            $rules['travel_date'][] = 'after_or_equal:' . $this->earliestCreatableDate($user);
        }

        return $request->validate($rules);
    }

    private function earliestCreatableDate(User $user): string
    {
        return ($this->isDriver($user) ? CarbonImmutable::yesterday() : CarbonImmutable::today())->toDateString();
    }

    private function driversForCurrentTeam(User $user): array
    {
        $team = $user->currentTeam;

        if (!$team || strcasecmp(trim($team->name), 'GOALI TRANSPORTES') !== 0) {
            return [];
        }

        return $team->users()
            ->get(['users.id', 'users.name'])
            ->sortBy('name')
            ->map(fn(User $driver) => ['id' => $driver->id, 'name' => $driver->name])
            ->values()
            ->all();
    }

    private function solicitationOptionsForUser(User $user): array
    {
        return $this->queryForUser($user)
            ->whereNotNull('solicitation')
            ->where('solicitation', '<>', '')
            ->distinct()
            ->pluck('solicitation')
            ->merge(
                GoaliCompany::query()
                    ->where('team_id', $user->current_team_id)
                    ->pluck('name')
            )
            ->filter(fn(string $solicitation) => trim($solicitation) !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function filterableDriverIdsForCurrentTeam(User $user): array
    {
        $team = $user->currentTeam;

        if (! $team || strcasecmp(trim($team->name), 'GOALI TRANSPORTES') !== 0) {
            return [];
        }

        if ($this->isGoaliScopedManager($user) && ! $this->isAdmin($user)) {
            return $this->assignedDriverIdsForCurrentTeam($user);
        }

        return $team->users()->pluck('users.id')->all();
    }

    private function filterDriversForCurrentUser(User $user): array
    {
        $drivers = $this->driversForCurrentTeam($user);

        if (! $this->isGoaliScopedManager($user) || $this->isAdmin($user)) {
            return $drivers;
        }

        $assignedDriverIds = $this->assignedDriverIdsForCurrentTeam($user);

        return array_values(array_filter(
            $drivers,
            fn($driver) => in_array((int) $driver['id'], $assignedDriverIds, true),
        ));
    }

    private function tripDriverOptionsForCurrentUser(User $user): array
    {
        $drivers = $this->driversForCurrentTeam($user);

        if (! $this->isGoaliScopedManager($user)) {
            return $drivers;
        }

        $allowedDriverIds = array_fill_keys([
            (int) $user->id,
            ...$this->assignedDriverIdsForCurrentTeam($user),
        ], true);

        return array_values(array_filter(
            $drivers,
            fn($driver) => isset($allowedDriverIds[(int) $driver['id']]),
        ));
    }

    private function isGoaliScopedManager(User $user): bool
    {
        $team = $user->currentTeam;
        $role = strtolower(trim((string) $user->getRoleByUser()));

        return $team
            && strcasecmp(trim($team->name), 'GOALI TRANSPORTES') === 0
            && in_array($role, ['admin', 'administrativo'], true);
    }

    private function assignedDriverIdsForCurrentTeam(User $user): array
    {
        if (! $user->current_team_id) {
            return [];
        }

        return DB::table('goali_driver_assignments')
            ->join('team_user', function ($join) {
                $join->on('team_user.team_id', '=', 'goali_driver_assignments.team_id')
                    ->on('team_user.user_id', '=', 'goali_driver_assignments.driver_id');
            })
            ->where('goali_driver_assignments.team_id', $user->current_team_id)
            ->where('goali_driver_assignments.manager_id', $user->id)
            ->where('team_user.role', 'Motorista')
            ->pluck('goali_driver_assignments.driver_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function companiesForCurrentTeam(User $user): array
    {
        return GoaliCompany::query()
            ->where('team_id', $user->current_team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'departure_address'])
            ->toArray();
    }

    private function passengersForCurrentTeam(User $user): array
    {
        return GoaliPassenger::query()
            ->where('team_id', $user->current_team_id)
            ->with('company:id,name,departure_address')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'boarding_point', 'phone'])
            ->toArray();
    }

    private function applyDirectoryValues(array $validated, User $user): array
    {
        if (! empty($validated['company_id'])) {
            $company = GoaliCompany::query()
                ->where('team_id', $user->current_team_id)
                ->findOrFail($validated['company_id']);
            $validated['solicitation'] = $company->name;
        }

        if (! empty($validated['passenger_ids'])) {
            $passengers = GoaliPassenger::query()
                ->where('team_id', $user->current_team_id)
                ->whereIn('id', $validated['passenger_ids'])
                ->get(['id', 'company_id', 'name']);
            abort_unless($passengers->count() === count(array_unique($validated['passenger_ids'])), 422);
            abort_unless($passengers->every(fn(GoaliPassenger $passenger) => empty($validated['company_id']) || (int) $passenger->company_id === (int) $validated['company_id']), 422);
            $validated['passenger'] = $passengers->sortBy(fn(GoaliPassenger $passenger) => array_search($passenger->id, $validated['passenger_ids'], true))->pluck('name')->implode("\n");
            $validated['passenger_id'] = $validated['passenger_ids'][0];
        } elseif (! empty($validated['passenger_id'])) {
            $passenger = GoaliPassenger::query()
                ->where('team_id', $user->current_team_id)
                ->findOrFail($validated['passenger_id']);
            abort_unless(empty($validated['company_id']) || (int) $passenger->company_id === (int) $validated['company_id'], 422);
            $validated['passenger'] = $passenger->name;
            $validated['passenger_ids'] = [$passenger->id];
        }

        return $validated;
    }
}
