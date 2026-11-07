<?php

namespace App\Http\Controllers;

use App\Models\GoaliTrip;
use App\Models\Spatie\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GoaliTripController extends Controller
{
    private const DRIVER_ROLES = ['Motorista', 'Admin'];
    private const TRIP_STATUSES = ['Criada', 'Em Andamento', 'Aguardando Analise', 'Finalizada'];

    private const SOLICITATIONS = [
        'ELADECORA', 'IMB', 'OSESP', 'FRANGO DOURADO', 'INFINITYLLOG',
        'LOGVALE', 'CMW', 'REINALDO', 'PROHALL', 'INVENTA',
    ];

    public function form()
    {
        $user = Auth::user();

        return Inertia::render('Goali/Form', [
            'solicitations' => self::SOLICITATIONS,
            'responsibleUser' => $user?->name ?? '',
            'initialTripStatus' => $user && ($this->isAdmin($user) || $this->isDriver($user))
                ? 'Em Andamento'
                : 'Criada',
            'currentUserId' => $user?->id,
            'drivers' => $user ? $this->driversForCurrentTeam($user) : [],
            'initialDriverId' => $user && collect($this->driversForCurrentTeam($user))->contains('id', $user->id)
                ? $user->id
                : null,
            'isAdmin' => $user ? $this->isAdmin($user) : false,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $rangeEndDate = null;

        if ($request->exists('travel_start_date') || $request->exists('travel_end_date')) {
            $dateRange = $request->validate([
                'travel_start_date' => ['required', 'date_format:Y-m-d'],
                'travel_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:travel_start_date'],
                'travel_time' => ['required', 'date_format:H:i'],
            ]);

            $rangeEndDate = $dateRange['travel_end_date'];
            $request->merge(['travel_date' => $dateRange['travel_start_date']]);
        }

        $validated = $this->validatedTrip($request, $user);
        $validated['user_id'] = $user->getAuthIdentifier();
        $validated['responsible'] = $user->name;
        $adminCreatedForAnotherOrUnassigned = $this->isAdmin($user)
            && (empty($validated['driver_id'])
                || (int) $validated['driver_id'] !== (int) $user->getAuthIdentifier());
        $validated['trip_status'] = $adminCreatedForAnotherOrUnassigned
            ? 'Criada'
            : (($this->isAdmin($user) || $this->isDriver($user)) ? 'Em Andamento' : 'Criada');

        DB::transaction(function () use ($validated, $rangeEndDate) {
            $date = CarbonImmutable::parse($validated['travel_date']);
            $endDate = CarbonImmutable::parse($rangeEndDate ?? $validated['travel_date']);

            for (; $date->lessThanOrEqualTo($endDate); $date = $date->addDay()) {
                GoaliTrip::create([
                    ...$validated,
                    'travel_date' => $date->toDateString(),
                ]);
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
        $filters = $this->validatedFilters($request);
        $user = $request->user();
        $query = $this->filteredQuery($filters, $user);
        $summary = (clone $query)
            ->reorder()
            ->selectRaw(
                'COUNT(*) as count, COALESCE(SUM(amount), 0) as amount, ' .
                'COALESCE(SUM(CASE WHEN trip_status = ? THEN amount ELSE 0 END), 0) as receivable',
                ['Finalizada']
            )
            ->first();
        $records = $query->get();

        return Inertia::render('Goali/Index', [
            'records' => $records,
            'summary' => [
                'count' => (int) $summary->count,
                'amount' => (float) $summary->amount,
                'receivable' => (float) $summary->receivable,
            ],
            'filters' => $filters,
            'isAdmin' => $this->isAdmin($user),
            'isDriver' => $this->isDriver($user),
            'currentDriverId' => $this->isDriver($user) ? $user->id : null,
            'solicitations' => self::SOLICITATIONS,
            'tripStatuses' => self::TRIP_STATUSES,
            'drivers' => $this->driversForCurrentTeam($user),
        ]);
    }

    public function update(Request $request, GoaliTrip $trip)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);
        $isDriver = $this->isDriver($user);
        abort_unless($isAdmin || $isDriver, 403);

        if ($request->boolean('cancel_trip')) {
            $request->validate(['cancel_trip' => ['required', 'boolean']]);
            DB::transaction(function () use ($user, $isAdmin, $trip) {
                $lockedTrip = $this->queryForUser($user)
                    ->lockForUpdate()
                    ->findOrFail($trip->getKey());

                abort_unless($lockedTrip->trip_status !== 'Finalizada', 403);

                $lockedTrip->update([
                    'trip_status' => 'Criada',
                    'driver_id' => null,
                    'cancelled_by_name' => $user->name,
                    'cancelled_by_role' => $isAdmin ? 'Admin' : 'Motorista',
                    'cancelled_at' => now(),
                ]);
            });

            return back()->with('success', 'Corrida cancelada e devolvida às corridas pendentes.');
        }

        if ($isDriver) {
            $validated = $request->validate([
                'trip_status' => ['required', Rule::in(['Em Andamento', 'Aguardando Analise'])],
                'driver_id' => ['required', 'integer', Rule::in([$user->id])],
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
                        'cancelled_by_name' => null,
                        'cancelled_by_role' => null,
                        'cancelled_at' => null,
                    ]);
                    return;
                }

                abort_unless(
                    $lockedTrip->trip_status === 'Em Andamento'
                        && $validated['trip_status'] === 'Aguardando Analise',
                    403
                );
                abort_unless(
                    $lockedTrip->driver_id === null || (int) $lockedTrip->driver_id === (int) $user->id,
                    403
                );

                $lockedTrip->update([
                    'driver_id' => $user->id,
                    'trip_status' => 'Aguardando Analise',
                    'cancelled_by_name' => null,
                    'cancelled_by_role' => null,
                    'cancelled_at' => null,
                ]);
            });

            return back()->with(
                'success',
                $validated['trip_status'] === 'Em Andamento'
                    ? 'Corrida aceita com sucesso.'
                    : 'Corrida confirmada e enviada para análise.'
            );
        }

        $trip = $this->queryForUser($user)->findOrFail($trip->getKey());
        abort_if($trip->trip_status === 'Finalizada', 403, 'Corridas finalizadas não podem ser editadas.');

        $validated = $this->validatedTrip($request, $user);
        $nextStatus = match ($trip->trip_status) {
            'Criada' => 'Em Andamento',
            'Em Andamento' => 'Finalizada',
            'Aguardando Analise' => 'Finalizada',
            default => null,
        };
        $requestedStatus = $validated['trip_status'] ?? null;

        if ($requestedStatus !== null) {
            abort_unless($requestedStatus === $nextStatus, 403);
            $validated['trip_status'] = $requestedStatus;
            $validated['cancelled_by_name'] = null;
            $validated['cancelled_by_role'] = null;
            $validated['cancelled_at'] = null;
        } else {
            unset($validated['trip_status']);
        }

        $trip->update($validated);

        return back()->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(Request $request, GoaliTrip $trip)
    {
        $user = $request->user();
        abort_unless($this->isAdmin($user), 403);

        $trip = $this->queryForUser($user)->findOrFail($trip->getKey());
        $trip->delete();

        return back()->with('success', 'Registro excluído com sucesso.');
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $records = $this->filteredQuery($filters, $request->user())->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Relatório Goali');
        $headers = ['Data', 'Horário', 'Tipo', 'Status da corrida', 'Solicitação', 'Origem', 'Destino', 'Passageiro', 'Motorista', 'Responsável', 'Valor (R$)', 'Cancelada por'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
        }

        foreach ($records as $rowIndex => $record) {
            $values = [
                $record->travel_date->format('d/m/Y'),
                substr($record->travel_time, 0, 5),
                $record->status,
                $record->trip_status,
                $record->solicitation,
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

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $tempFile = tempnam(sys_get_temp_dir(), 'goali-report-');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, 'relatorio_goali_' . now()->format('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    private function validatedFilters(Request $request): array
    {
        $dateFilterProvided = $request->query->has('startDate') || $request->query->has('endDate');
        $filters = $request->validate([
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'status' => ['nullable', 'in:ENTRADA,SAÍDA'],
            'tripStatus' => ['nullable', Rule::in(self::TRIP_STATUSES)],
            'solicitation' => ['nullable', 'string', 'in:' . implode(',', self::SOLICITATIONS)],
            'sortOrder' => ['nullable', 'in:asc,desc'],
        ]);
        $dateDefaults = $dateFilterProvided
            ? ['startDate' => '', 'endDate' => '']
            : [
                'startDate' => now()->startOfMonth()->toDateString(),
                'endDate' => now()->endOfMonth()->toDateString(),
            ];

        return array_merge([
            ...$dateDefaults,
            'status' => '',
            'tripStatus' => '',
            'solicitation' => '',
            'sortOrder' => 'desc',
        ], $filters);
    }

    private function filteredQuery(array $filters, User $user)
    {
        $sortOrder = $filters['sortOrder'] ?? 'desc';

        return $this->queryForUser($user)
            ->when($filters['startDate'] ?? null, fn ($query, $date) => $query->whereDate('travel_date', '>=', $date))
            ->when($filters['endDate'] ?? null, fn ($query, $date) => $query->whereDate('travel_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['tripStatus'] ?? null, fn ($query, $status) => $query->where('trip_status', $status))
            ->when($filters['solicitation'] ?? null, fn ($query, $solicitation) => $query->where('solicitation', $solicitation))
            ->with('driver:id,name')
            ->orderBy('travel_date', $sortOrder)
            ->orderBy('travel_time', $sortOrder)
            ->orderBy('id', $sortOrder);
    }

    private function queryForUser(User $user)
    {
        $query = GoaliTrip::query();

        if ($this->isDriver($user)) {
            $query->where(function ($query) use ($user) {
                $query->where('driver_id', $user->id)
                    ->orWhereNull('driver_id');
            });
        }

        return $query;
    }

    private function isDriver(User $user): bool
    {
        return strcasecmp(trim((string) $user->getRoleByUser()), 'Motorista') === 0;
    }

    private function isAdmin(User $user): bool
    {
        return strcasecmp(trim((string) $user->getRoleByUser()), 'Admin') === 0;
    }

    private function rules(User $user): array
    {
        return [
            'travel_date' => ['required', 'date'],
            'travel_time' => ['required', 'date_format:H:i'],
            'status' => ['required', 'in:ENTRADA,SAÍDA'],
            'trip_status' => ['sometimes', 'nullable', Rule::in(self::TRIP_STATUSES)],
            'driver_id' => [
                $this->isAdmin($user) ? 'nullable' : 'required',
                'required_if:trip_status,Finalizada',
                'integer',
                Rule::in($this->isAdmin($user)
                    ? array_column($this->driversForCurrentTeam($user), 'id')
                    : [$user->id]),
            ],
            'solicitation' => ['required', 'in:' . implode(',', self::SOLICITATIONS)],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'passenger' => ['required', 'string', 'max:255'],
            'responsible' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ];
    }

    private function validatedTrip(Request $request, User $user): array
    {
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

        return $request->validate($this->rules($user));
    }

    private function driversForCurrentTeam(User $user): array
    {
        $team = $user->currentTeam;

        if (!$team || strcasecmp(trim($team->name), 'GOALI TRANSPORTES') !== 0) {
            return [];
        }

        return $team->users()
            ->wherePivotIn('role', self::DRIVER_ROLES)
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $driver) => ['id' => $driver->id, 'name' => $driver->name])
            ->values()
            ->all();
    }
}