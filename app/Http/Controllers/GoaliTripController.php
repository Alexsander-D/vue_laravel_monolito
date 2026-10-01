<?php

namespace App\Http\Controllers;

use App\Models\GoaliTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GoaliTripController extends Controller
{
    private const SOLICITATIONS = [
        'ELADECORA', 'IMB', 'OSESP', 'FRANGO DOURADO', 'INFINITYLLOG',
        'LOGVALE', 'CMW', 'REINALDO', 'PROHALL', 'INVENTA',
    ];

    private const AMOUNTS = [30, 40, 45, 50, 60, 65, 70, 80, 90, 95, 100, 115, 120, 140, 150, 180, 230, 250];

    public function form()
    {
        return Inertia::render('Goali/Form', [
            'solicitations' => self::SOLICITATIONS,
            'amounts' => self::AMOUNTS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['user_id'] = Auth::id();
        GoaliTrip::create($validated);

        return back()->with('success', 'Registro enviado com sucesso.');
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'status' => ['nullable', 'in:ENTRADA,SAÍDA'],
            'solicitation' => ['nullable', 'string', 'in:' . implode(',', self::SOLICITATIONS)],
        ]);
        $summary = (clone $this->filteredQuery($filters))
            ->reorder()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as amount')
            ->first();
        $records = $this->filteredQuery($filters)->paginate(50)->withQueryString();

        return Inertia::render('Goali/Index', [
            'records' => $records,
            'summary' => ['count' => (int) $summary->count, 'amount' => (float) $summary->amount],
            'filters' => array_merge(['startDate' => '', 'endDate' => '', 'status' => '', 'solicitation' => ''], $filters),
            'solicitations' => self::SOLICITATIONS,
            'amounts' => self::AMOUNTS,
        ]);
    }

    public function update(Request $request, GoaliTrip $trip)
    {
        $trip->update($request->validate($this->rules()));

        return back()->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(GoaliTrip $trip)
    {
        $trip->delete();

        return back()->with('success', 'Registro excluído com sucesso.');
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'status' => ['nullable', 'in:ENTRADA,SAÍDA'],
            'solicitation' => ['nullable', 'string', 'in:' . implode(',', self::SOLICITATIONS)],
        ]);
        $records = $this->filteredQuery($filters)->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Relatório Goali');
        $headers = ['Data', 'Horário', 'Status', 'Solicitação', 'Origem', 'Destino', 'Passageiro', 'Valor (R$)'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
        }

        foreach ($records as $rowIndex => $record) {
            $values = [
                $record->travel_date->format('d/m/Y'),
                substr($record->travel_time, 0, 5),
                $record->status,
                $record->solicitation,
                $record->origin,
                $record->destination,
                $record->passenger,
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

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $tempFile = tempnam(sys_get_temp_dir(), 'goali-report-');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, 'relatorio_goali_' . now()->format('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    private function filteredQuery(array $filters)
    {
        return GoaliTrip::query()
            ->when($filters['startDate'] ?? null, fn ($query, $date) => $query->whereDate('travel_date', '>=', $date))
            ->when($filters['endDate'] ?? null, fn ($query, $date) => $query->whereDate('travel_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['solicitation'] ?? null, fn ($query, $solicitation) => $query->where('solicitation', $solicitation))
            ->orderByDesc('travel_date')
            ->orderByDesc('travel_time');
    }

    private function rules(): array
    {
        return [
            'travel_date' => ['required', 'date'],
            'travel_time' => ['required', 'date_format:H:i'],
            'status' => ['required', 'in:ENTRADA,SAÍDA'],
            'solicitation' => ['required', 'in:' . implode(',', self::SOLICITATIONS)],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'passenger' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'in:' . implode(',', self::AMOUNTS)],
        ];
    }
}