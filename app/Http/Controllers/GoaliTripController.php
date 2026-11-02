<?php

namespace App\Http\Controllers;

use App\Models\GoaliTrip;
use App\Models\Spatie\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GoaliTripController extends Controller
{
    private const DRIVER_ROLES = ['Motorista', 'Admin'];
    private const TRIP_STATUSES = ['Criada', 'Em Andamento', 'Finalizada'];

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
            'initialTripStatus' => $user && $this->isAdmin($user) ? 'Em Andamento' : 'Criada',
            'drivers' => $user ? $this->driversForCurrentTeam($user) : [],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $this->validatedTrip($request, $user);
        $validated['user_id'] = $user->getAuthIdentifier();
        $validated['responsible'] = $user->name;
        $validated['trip_status'] = $this->isAdmin($user) ? 'Em Andamento' : 'Criada';
        GoaliTrip::create($validated);

        return back()->with('success', 'Registro enviado com sucesso.');
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
            'solicitations' => self::SOLICITATIONS,
            'tripStatuses' => self::TRIP_STATUSES,
            'drivers' => $this->driversForCurrentTeam($user),
        ]);
    }

    public function update(Request $request, GoaliTrip $trip)
    {
        $user = $request->user();
        abort_unless($this->isAdmin($user), 403);

        $trip = $this->queryForUser($user)->findOrFail($trip->getKey());
        $validated = $this->validatedTrip($request, $user);
        $validated['trip_status'] = $trip->trip_status;

        $trip->update($validated);

        return back()->with('success', 'Registro atualizado com sucesso.');
    }

    public function advanceStatus(Request $request, GoaliTrip $trip)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);
        $isDriver = $this->isDriver($user);

        abort_unless($isAdmin || $isDriver, 403);

        $trip = $this->queryForUser($user)->findOrFail($trip->getKey());
        $nextStatus = match ($trip->trip_status) {
            'Criada' => $isAdmin ? 'Em Andamento' : null,
            'Em Andamento' => 'Finalizada',
            default => null,
        };

        abort_unless($nextStatus, 403);

        $trip->update(['trip_status' => $nextStatus]);

        return back()->with('success', 'Status da corrida atualizado com sucesso.');
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
        $headers = ['Data', 'Horário', 'Tipo', 'Status da corrida', 'Solicitação', 'Origem', 'Destino', 'Passageiro', 'Motorista', 'Responsável', 'Valor (R$)'];

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

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
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
                    ->orWhere(function ($query) use ($user) {
                        $query->whereNull('driver_id')
                            ->where('responsible', $user->name);
                    });
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
            'trip_status' => ['sometimes', 'required', Rule::in(self::TRIP_STATUSES)],
            'driver_id' => [
                'required',
                'integer',
                Rule::in(array_column($this->driversForCurrentTeam($user), 'id')),
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