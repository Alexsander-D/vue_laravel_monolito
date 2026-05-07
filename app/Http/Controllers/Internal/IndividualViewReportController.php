<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\Queue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class IndividualViewReportController extends Controller
{
    /**
     * Mostra uma lista de recursos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $startDate = $request->input('startDate') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('endDate') ?? now()->endOfMonth()->format('Y-m-d');

        return Inertia::render('Internal/IndividualViewReport', [
            'date' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        ]);
    }

    /**
     * Retorna dados para o DataTable.
     *
     * @return \Yajra\DataTables\DataTables
     */
    public function datatable()
    {
        $startDate = request('startDate');
        $endDate = request('endDate');

        $data = $this->data_reports($startDate, $endDate);
        return DataTables::of($data)->make(true);
    }

    /**
     * Retorna dados para exportar.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export($startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $info = $this->data_reports($startDate, $endDate);

        $headers = [
            "ID",
            "Criado em",
            "Responsável",
            "Produto Entrada",
            "Produto Saída",
            "Transformação",
            "Família",
            "Componente",
            "Defeito",
            "Solução",
            "Serial Number",
            "Lote",
            "Observação",
            "Atualizado em",
            "Status",
            "Data Embalagem",
            "Responsável Embalagem",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }
        $rowIndex = 2;
        foreach ($info as $data) {
            $sheet->setCellValue('A' . $rowIndex, $data['id']);
            $sheet->setCellValue('B' . $rowIndex, $data['created_at']);
            $sheet->setCellValue('C' . $rowIndex, $data['user_name']);
            $sheet->setCellValue('D' . $rowIndex, $data['product']);
            $sheet->setCellValue('E' . $rowIndex, $data['product_new']);
            $sheet->setCellValue('F' . $rowIndex, $data['transformed']);
            $sheet->setCellValue('G' . $rowIndex, $data['family']);
            $sheet->setCellValue('H' . $rowIndex, $data['component']);
            $sheet->setCellValue('I' . $rowIndex, $data['defect']);
            $sheet->setCellValue('J' . $rowIndex, $data['solution']);
            $sheet->setCellValue('K' . $rowIndex, $data['serial_number']);
            $sheet->setCellValue('L' . $rowIndex, $data['lot']);
            $sheet->setCellValue('M' . $rowIndex, $data['observation']);
            $sheet->setCellValue('N' . $rowIndex, $data['updated_at']);
            $sheet->setCellValue('O' . $rowIndex, $data['status']);
            $sheet->setCellValue('P' . $rowIndex, $data['output_date']);
            $sheet->setCellValue('Q' . $rowIndex, $data['output_user_name']);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'reports_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Retorna dados para o DataTable e exportar.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Support\Collection
     */
    private function data_reports($startDate, $endDate)
    {
        $user = Auth::user();
        $outputs = Queue::with([
            'entry',
            'user',
            'reports.defectSolution.component',
            'products',
            'productOutput.user'
        ])
            ->whereDate('updated_at', '>=', $startDate)
            ->whereDate('updated_at', '<=', $endDate)
            ->where('user_id', $user->id)
            ->get();

        return $outputs->flatMap(function ($output) {
            return $output->reports->take(2)->map(function ($report) use ($output) {
                return [
                    'id' => $output->id,
                    'created_at' => $output->created_at->format('d/m/Y H:i:s'),
                    'user_name' => $output->user->name,
                    'product' => $output->product,
                    'product_new' => $output->product_new,
                    'transformed' => $output->product_new === $output->product ? 0 : 1,
                    'family' => $output->products->family,
                    'component' => $report->defectSolution?->component->component,
                    'defect' => $report->defectSolution?->defect,
                    'solution' => $report->defectSolution?->solution,
                    'serial_number' => $output->serial_number,
                    'lot' => $output->product_lot,
                    'observation' => $output->observation,
                    'updated_at' => $output->updated_at->format('d/m/Y H:i:s'),
                    'status' => $output->status,
                    'output_date' => $output->productOutput ? $output->productOutput->created_at->format('d/m/Y H:i:s') : null,
                    'output_user_name' => $output->productOutput->user->name ?? null,
                ];
            });
        });
    }
}

