<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\ScreeningDefectsSolution;
use App\Models\External\ScreeningReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class ProductivityReportController extends Controller
{
    public function show($screeningReportId)
    {
        $screeningReport = ScreeningReport::with([
            'products',
            'screening.customers:id,company_name,government'
        ])
            ->select([
                'id',
                'screening_id',
                'imei1',
                'imei2',
                'serial_number',
                'hardware_version',
                'qr_code',
                'gemco',
                'seal',
                'fm',
                'UniqueID',
                'patrimony',
                'observation',
                'status',
                'guarantee'
            ])
            ->findOrFail($screeningReportId);

        $products = ScreeningReport::with([
            'products:id,sku,family'
        ])
            ->where('screening_id', $screeningReport->screening_id)
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'sku' => $report->products->sku ?? '',
                    'family' => $report->products->family ?? '',
                ];
            });

        $defectsSolutions = ScreeningDefectsSolution::with([
            'component:id,component',
            'defectSolution:id,defect,solution'
        ])
            ->where('screening_report_id', $screeningReport->id)
            ->get()
            ->map(function ($item) {
                return [
                    'component' => $item->component->component ?? '',
                    'defect' => $item->defectSolution->defect ?? '',
                    'solution' => $item->defectSolution->solution ?? '',
                ];
            });

        return Inertia::render('ExternalService/CustomerService/ProductivityReport', [
            'screeningReport' => $screeningReport,
            'productivityData' => [
                'screeningId' => (int) $screeningReport->screening_id,
                'company_name' => $screeningReport->screening->company_name ?? '',
                'government' => $screeningReport->screening->customers->government ?? '',
                'products' => $products->toArray(),
                'screeningReportId' => $screeningReportId,
                'imei1' => $screeningReport->imei1 ?? '',
                'imei2' => $screeningReport->imei2 ?? '',
                'serial_number' => $screeningReport->serial_number ?? '',
                'hardware_version' => $screeningReport->hardware_version ?? '',
                'qr_code' => $screeningReport->qr_code ?? '',
                'gemco' => $screeningReport->gemco ?? '',
                'seal' => $screeningReport->seal ?? '',
                'fm' => $screeningReport->fm ?? '',
                'UniqueID' => $screeningReport->UniqueID ?? '',
                'patrimony' => $screeningReport->patrimony ?? '',
                'observation' => $screeningReport->observation ?? '',
                'status' => $screeningReport->status ?? '',
                'guarantee' => $screeningReport->guarantee ?? '',
                'defectsSolutions' => $defectsSolutions->toArray(),
            ]
        ]);
    }
    public function datatable($screeningId)
    {
        // Detecta se está em produção
        $isProduction = app()->environment('production');

        // Define o prefixo do schema apenas se for produção
        $schema = $isProduction ? 'assistencia_vex.' : '';

        $query = DB::table("{$schema}screening_report as sr")
            ->leftJoin("{$schema}products as p", 'p.id', '=', 'sr.products_id')
            ->leftJoin("{$schema}screening_defects_solutions as sds", 'sr.id', '=', 'sds.screening_report_id')
            ->leftJoin("{$schema}components as c", 'sds.component_id', '=', 'c.id')
            ->leftJoin("{$schema}defects_solutions as ds", 'sds.defects_solutions_id', '=', 'ds.id')
            ->where('sr.screening_id', $screeningId)
            ->select([
                'sr.id',
                'sr.status',
                'sr.imei1',
                'sr.imei2',
                'sr.serial_number',
                'sr.observation',
                'sr.guarantee',
                'p.sku as product',
                'c.component',
                'ds.defect',
                'ds.solution',
            ]);

        // ✅ Usa collection para ativar client-side
        $data = $query->get();

        return DataTables::of($data)
            ->editColumn('status', function ($row) {
                $status = strtolower($row->status ?? '');
                $color = match ($status) {
                    'recuperado' => 'text-green-600',
                    'devolução' => 'text-blue-600',
                    'mau uso' => 'text-red-600',
                    'próxima triagem' => 'text-orange-600',
                    'produto não encontrado' => 'text-purple-600',
                    'pendente' => 'text-yellow-500',
                    'fora de garantia' => 'text-red-700',
                    default => 'text-gray-600',
                };
                return "<span class='px-2 py-1 rounded {$color}'>" . strtoupper($row->status ?? '') . "</span>";
            })
            ->addColumn('action', function ($row) {
                $url = route('productivityReport.show', ['screeningReportId' => $row->id]);
                return '
            <a href="' . $url . '">
                <button type="button"
                    class="flex justify-center items-center size-[38px] text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none transition-all duration-150 ease-in-out"
                    title="Visualizar relatório">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 4.5c-7.633 0-10 7.5-10 7.5s2.367 7.5 10 7.5 10-7.5 10-7.5-2.367-7.5-10-7.5z" />
                        <circle cx="12" cy="12" r="3.5" />
                    </svg>
                </button>
            </a>
        ';
            })
            ->editColumn('product', function ($row) {
                if ($row->guarantee && strtolower($row->guarantee) === 'fora de garantia') {
                    return "<span class='text-red-700 cursor-help' title='Produto fora de garantia'>{$row->product}</span>";
                }
                return $row->product ?: "<span class='text-gray-400 italic'>Sem produto</span>";
            })
            ->rawColumns(['status', 'action', 'product'])
            ->make(true);
    }

    public function export(Request $request, $screeningId)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos da planilha
        $headers = [
            "ID",
            "Produto",
            "Componente",
            "Defeito",
            "Solução",
            "IMEI 1",
            "IMEI 2",
            "N° de Série",
            "Status",
            "Garantia",
            "Observação",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        // Consulta dos dados de produtividade
        $query = DB::table('screening_report')
            ->leftJoin('screening_defects_solutions', 'screening_report.id', '=', 'screening_defects_solutions.screening_report_id')
            ->leftJoin('components', 'screening_defects_solutions.component_id', '=', 'components.id')
            ->leftJoin('defects_solutions', 'screening_defects_solutions.defects_solutions_id', '=', 'defects_solutions.id')
            ->select(
                'screening_report.id',
                'screening_defects_solutions.product',
                'components.component',
                'defects_solutions.defect',
                'defects_solutions.solution',
                'screening_report.imei1',
                'screening_report.imei2',
                'screening_report.serial_number',
                'screening_report.status',
                'screening_report.guarantee',
                'screening_report.observation'
            )
            ->where('screening_report.screening_id', $screeningId)
            ->orderBy('screening_report.id', 'asc')
            ->get();

        // Preenche as linhas
        $rowIndex = 2;
        foreach ($query as $row) {
            $sheet->setCellValue('A' . $rowIndex, $row->id);
            $sheet->setCellValue('B' . $rowIndex, strtoupper($row->product ?? ''));
            $sheet->setCellValue('C' . $rowIndex, strtoupper($row->component ?? ''));
            $sheet->setCellValue('D' . $rowIndex, strtoupper($row->defect ?? ''));
            $sheet->setCellValue('E' . $rowIndex, strtoupper($row->solution ?? ''));
            $sheet->setCellValue('F' . $rowIndex, $row->imei1 ?? '');
            $sheet->setCellValue('G' . $rowIndex, $row->imei2 ?? '');
            $sheet->setCellValue('H' . $rowIndex, $row->serial_number ?? '');
            $sheet->setCellValue('I' . $rowIndex, strtoupper($row->status ?? ''));
            $sheet->setCellValue('J' . $rowIndex, strtoupper($row->guarantee ?? ''));
            $sheet->setCellValue('K' . $rowIndex, $row->observation ?? '');
            $rowIndex++;
        }

        // Cria o arquivo temporário e envia para download
        $writer = new Xlsx($spreadsheet);
        $filename = 'productivity_report_' . $screeningId . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
