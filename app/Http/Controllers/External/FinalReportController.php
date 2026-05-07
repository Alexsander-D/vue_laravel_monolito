<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Customers;
use App\Models\External\Screening;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningDefectsSolution;
use App\Models\External\TechnicalScale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;

class FinalReportController extends Controller

{
    /**
     * Exibe a tela de relatório final.
     *
     * @param int $screeningId
     * @return \Inertia\Response
     */
    public function index($screeningId)
    {
        /** @var \App\Models\Spatie\User $user */
        $user = Auth::user();

        // Pega o papel do usuário no time atual (pivot da team_user)
        $userRole = $user?->teams()
            ->where('teams.id', $user->current_team_id)
            ->first()?->pivot?->role;

        // Admin sempre pode
        if (!in_array(strtolower($userRole), ['admin', 'auxiliar administrativo'])) {
            $isLinked = TechnicalScale::where('screening_id', $screeningId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$isLinked) {
                abort(403, 'Acesso negado.');
            }
        }

        $screening = Screening::find($screeningId);

        $finalReport = ScreeningReport::with([
            'screening:id,status',
            'products:id,sku,family',
        ])->where('screening_id', $screeningId)->get();

        $totalProducts = $finalReport->count();

        $groupedReport = $finalReport->groupBy(function ($entry) {
            return optional($entry->products)->sku . '-' . ($entry->guarantee === 'em garantia' ? 'em garantia' : 'fora de garantia');
        })->map(function ($groupedEntries, $key) {
            [$sku, $guaranteeStatus] = explode('-', $key);
            $firstEntry = $groupedEntries->first();

            $statusCounts = [
                'Recuperado' => 0,
                'Devolução' => 0,
                'Fora de garantia' => 0,
                'Mau uso' => 0,
                'Não encontrado' => 0,
                'Próxima triagem' => 0,
            ];

            foreach ($groupedEntries as $entry) {
                $status = trim($entry->status ?? 'N/A');
                if (array_key_exists($status, $statusCounts)) {
                    $statusCounts[$status]++;
                }
            }

            $defectsSolutions = ScreeningDefectsSolution::with(['component:id,component', 'defectSolution:id,defect,solution'])
                ->where('screening_report_id', $firstEntry->id)
                ->first();

            return [
                'product' => $sku,
                'family' => optional($firstEntry->products)->family ?? 'N/A',
                'warranty' => strtoupper($guaranteeStatus),
                'quantity' => count($groupedEntries),
                'status_counts' => $statusCounts,
                'imei1' => $firstEntry->imei1 ?? '',
                'imei2' => $firstEntry->imei2 ?? '',
                'serial_number' => $firstEntry->serial_number ?? '',
                'observation' => $firstEntry->observation ?? '',
                'guarantee' => $firstEntry->guarantee ?? '',
                'price' => $firstEntry->price ?? 0,
                'component' => optional($defectsSolutions?->component)->component ?? '',
                'defect' => optional($defectsSolutions?->defectSolution)->defect ?? '',
                'solution' => optional($defectsSolutions?->defectSolution)->solution ?? '',
            ];
        })->values();

        $products = $finalReport->pluck('product');

        $customersInfo = Customers::whereHas('screenings', function ($query) use ($screeningId) {
            $query->where('id', $screeningId);
        })->first();

        $technicians = Auth::user()->currentTeam->giveUsersByTeam();

        $technicalScales = TechnicalScale::where('screening_id', $screeningId)
            ->with('user:id,name')
            ->get();

        $finalReportData = [
            'groupedReport' => $groupedReport,
            'products' => $products,
            'technicians' => $technicians,
            'screeningId' => (int) $screeningId,
            'customersInfo' => $customersInfo,
            'technicalScales' => $technicalScales,
            'screeningStatus' => $screening?->status ?? 'N/A',
            'totalProducts' => $totalProducts,
            'screeningData' => [
                'service_start' => optional($screening->service_start)->format('d/m/Y') ?? 'Não informado',
                'completion_date' => optional($screening->completion_date)->format('d/m/Y') ?? 'Não informado',
                'reject_report' => $screening->reject_report ?? '',
                'observation' => $screening->observation ?? '',
                'approval_date' => optional($screening->approval_date)->format('d/m/Y') ?? '',
                'scheduling_date' => optional($screening->scheduling_date)->format('d/m/Y') ?? '',
                'type_service' => $screening->type_service ?? null,
                'rm' => $screening->rm ?? '',

                'recovered_value' => 'R$ ' . number_format(
                    $finalReport->where('status', 'Recuperado')->sum('price'),
                    2,
                    ',',
                    '.'
                ),
                'return_value' => 'R$ ' . number_format(
                    $finalReport->where('status', 'Devolução')->sum('price'),
                    2,
                    ',',
                    '.'
                ),
                'ndoa_value' => 'R$ ' . number_format(
                    $finalReport->where('status', 'Fora de garantia')->sum('price'),
                    2,
                    ',',
                    '.'
                ),
            ],

        ];

        return Inertia::render('ExternalService/CustomerService/FinalReport', [
            'finalReportData' => $finalReportData,
            'userRole' => $userRole,
        ]);
    }

    public function datatable($screeningId)
    {
        $isProduction = app()->environment('production');
        $schema = $isProduction ? 'assistencia_vex.' : '';

        $query = DB::table("{$schema}screening_report as sr")
            ->leftJoin("{$schema}products as p", 'p.id', '=', 'sr.products_id')
            ->leftJoin("{$schema}screening_defects_solutions as sds", 'sr.id', '=', 'sds.screening_report_id')
            ->leftJoin("{$schema}components as c", 'sds.component_id', '=', 'c.id')
            ->leftJoin("{$schema}defects_solutions as ds", 'sds.defects_solutions_id', '=', 'ds.id')
            ->where('sr.screening_id', $screeningId)
            ->select([
                'sr.id',
                'p.sku as product',
                'p.family as family',
                'sr.price',
                'c.component',
                'ds.defect',
                'ds.solution',
                'sr.imei1',
                'sr.imei2',
                'sr.serial_number',
                'sr.status',
                'sr.observation',
            ]);

        return DataTables::of($query)
            ->editColumn('price', function ($row) {
                return $row->price !== null
                    ? 'R$ ' . number_format($row->price, 2, ',', '.')
                    : '';
            })
            ->editColumn('status', function ($row) {
                return $row->status ? strtoupper($row->status) : 'N/A';
            })
            ->editColumn('product', function ($row) {
                return $row->product
                    ? e($row->product)
                    : "<span class='text-gray-400 italic'>Sem produto</span>";
            })
            ->editColumn('family', fn($row) => $row->family ?: 'N/A')
            ->editColumn('component', fn($row) => $row->component ?: '')
            ->editColumn('defect', fn($row) => $row->defect ?: '')
            ->editColumn('solution', fn($row) => $row->solution ?: '')
            ->editColumn('imei1', fn($row) => strtoupper($row->imei1 ?: ''))
            ->editColumn('imei2', fn($row) => strtoupper($row->imei2 ?: ''))
            ->editColumn('serial_number', fn($row) => strtoupper($row->serial_number ?: ''))
            ->editColumn('observation', fn($row) => ucfirst($row->observation ?: ''))
            ->rawColumns(['product'])
            ->make(true);
    }

    public function export(Request $request, $screeningId)
    {
        $isProduction = app()->environment('production');
        $schema = $isProduction ? 'assistencia_vex.' : '';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos da planilha — conforme suas colunas do datatable
        $headers = [
            "ID",
            "Produto",
            "Família",
            "Valor Unitário",
            "Componente",
            "Defeito",
            "Solução",
            "IMEI 1",
            "IMEI 2",
            "N° de Série",
            "Status",
            "Observação",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        // Consulta
        $query = DB::table("{$schema}screening_report as sr")
            ->leftJoin("{$schema}products as p", 'p.id', '=', 'sr.products_id')
            ->leftJoin("{$schema}screening_defects_solutions as sds", 'sr.id', '=', 'sds.screening_report_id')
            ->leftJoin("{$schema}components as c", 'sds.component_id', '=', 'c.id')
            ->leftJoin("{$schema}defects_solutions as ds", 'sds.defects_solutions_id', '=', 'ds.id')
            ->where('sr.screening_id', $screeningId)
            ->select([
                'sr.id',
                'p.sku as product',
                'p.family as family',
                'sr.price',
                'c.component',
                'ds.defect',
                'ds.solution',
                'sr.imei1',
                'sr.imei2',
                'sr.serial_number',
                'sr.status',
                'sr.observation',
            ])
            ->orderBy('sr.id', 'asc')
            ->get();

        // Preenche as linhas
        $rowIndex = 2;

        foreach ($query as $row) {
            $sheet->setCellValue('A' . $rowIndex, $row->id);
            $sheet->setCellValue('B' . $rowIndex, strtoupper($row->product ?? ''));
            $sheet->setCellValue('C' . $rowIndex, strtoupper($row->family ?? ''));
            $sheet->setCellValue('D' . $rowIndex, $row->price !== null ? number_format($row->price, 2, ',', '.') : '');
            $sheet->setCellValue('E' . $rowIndex, strtoupper($row->component ?? ''));
            $sheet->setCellValue('F' . $rowIndex, strtoupper($row->defect ?? ''));
            $sheet->setCellValue('G' . $rowIndex, strtoupper($row->solution ?? ''));
            $sheet->setCellValue('H' . $rowIndex, strtoupper($row->imei1 ?? ''));
            $sheet->setCellValue('I' . $rowIndex, strtoupper($row->imei2 ?? ''));
            $sheet->setCellValue('J' . $rowIndex, strtoupper($row->serial_number ?? ''));
            $sheet->setCellValue('K' . $rowIndex, strtoupper($row->status ?? ''));
            $sheet->setCellValue('L' . $rowIndex, ucfirst($row->observation ?? ''));
            $rowIndex++;
        }

        // Download
        $writer = new Xlsx($spreadsheet);
        $filename = 'final_report_' . $screeningId . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function datatableProducts($screeningId)
    {
        $isProduction = app()->environment('production');
        $schema = $isProduction ? 'assistencia_vex.' : '';

        // Consulta base
        $finalReport = DB::table("{$schema}screening_report as sr")
            ->leftJoin("{$schema}products as p", 'p.id', '=', 'sr.products_id')
            ->where('sr.screening_id', $screeningId)
            ->select([
                'p.sku',
                'p.family',
                'sr.guarantee',
                'sr.status',
                'sr.price',
            ])
            ->get();

        // Agrupamento por SKU + garantia
        $grouped = $finalReport->groupBy(function ($entry) {
            $guarantee = strtolower($entry->guarantee) === 'em garantia' ? 'EM GARANTIA' : 'FORA DE GARANTIA';
            return "{$entry->sku}-{$guarantee}";
        })->map(function ($group, $key) {
            [$sku, $guaranteeStatus] = explode('-', $key);

            $statusCounts = [
                'Recuperado' => 0,
                'Devolução' => 0,
                'Mau uso' => 0,
                'Não encontrado' => 0,
                'Próxima triagem' => 0,
            ];

            foreach ($group as $item) {
                $status = trim($item->status ?? '');
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }

            return [
                'family' => $group->first()->family ?? 'N/A',
                'product' => $sku,
                'warranty' => strtoupper($guaranteeStatus),
                'total' => $group->count(),
                'recovered' => $statusCounts['Recuperado'],
                'return' => $statusCounts['Devolução'],
                'misuse' => $statusCounts['Mau uso'],
                'not_found' => $statusCounts['Não encontrado'],
                'next_screening' => $statusCounts['Próxima triagem'],
            ];
        })->values();

        return DataTables::of($grouped)->make(true);
    }

    public function exportProducts(Request $request, $screeningId)
    {
        $isProduction = app()->environment('production');
        $schema = $isProduction ? 'assistencia_vex.' : '';

        // Consulta base
        $finalReport = DB::table("{$schema}screening_report as sr")
            ->leftJoin("{$schema}products as p", 'p.id', '=', 'sr.products_id')
            ->where('sr.screening_id', $screeningId)
            ->select([
                'p.sku',
                'p.family',
                'sr.guarantee',
                'sr.status',
                'sr.price',
            ])
            ->get();

        // Agrupamento por SKU + garantia
        $grouped = $finalReport->groupBy(function ($entry) {
            $guarantee = strtolower($entry->guarantee) === 'em garantia' ? 'EM GARANTIA' : 'FORA DE GARANTIA';
            return "{$entry->sku}-{$guarantee}";
        })->map(function ($group, $key) {
            [$sku, $guaranteeStatus] = explode('-', $key);

            $statusCounts = [
                'Recuperado' => 0,
                'Devolução' => 0,
                'Mau uso' => 0,
                'Não encontrado' => 0,
                'Próxima triagem' => 0,
            ];

            foreach ($group as $item) {
                $status = trim($item->status ?? '');
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }

            return [
                'family' => $group->first()->family ?? 'N/A',
                'product' => $sku,
                'warranty' => strtoupper($guaranteeStatus),
                'total' => $group->count(),
                'recovered' => $statusCounts['Recuperado'],
                'return' => $statusCounts['Devolução'],
                'misuse' => $statusCounts['Mau uso'],
                'not_found' => $statusCounts['Não encontrado'],
                'next_screening' => $statusCounts['Próxima triagem'],
            ];
        })->values();

        // Cria planilha
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $headers = [
            "Família",
            "Produto",
            "Garantia",
            "Total",
            "Recuperado",
            "Devolução",
            "Mau Uso",
            "Não Encontrado",
            "Próxima Triagem",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        // Preenche linhas
        $rowIndex = 2;
        foreach ($grouped as $row) {
            $sheet->setCellValue('A' . $rowIndex, $row['family']);
            $sheet->setCellValue('B' . $rowIndex, $row['product']);
            $sheet->setCellValue('C' . $rowIndex, $row['warranty']);
            $sheet->setCellValue('D' . $rowIndex, $row['total']);
            $sheet->setCellValue('E' . $rowIndex, $row['recovered']);
            $sheet->setCellValue('F' . $rowIndex, $row['return']);
            $sheet->setCellValue('G' . $rowIndex, $row['misuse']);
            $sheet->setCellValue('H' . $rowIndex, $row['not_found']);
            $sheet->setCellValue('I' . $rowIndex, $row['next_screening']);
            $rowIndex++;
        }

        // Gera e envia o arquivo
        $writer = new Xlsx($spreadsheet);
        $filename = 'final_report_products_' . $screeningId . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

}
