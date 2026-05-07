<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ViewReportController extends Controller
{
    /**
     * Mostra o relatório de produção diário
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $team = Auth::user()->currentTeam;

        $users = $team->giveUsersByTeam();
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if (!$startDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
        }

        if (!$endDate) {
            $endDate = now()->endOfMonth()->format('Y-m-d');
        }

        return Inertia::render('Internal/ViewReport', [
            'allowedUsers' => $users,
            'date' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
            'filters' => [
                'productFilter' => $request->input('filters.productFilter', ''),
                'userFilter' => $request->input('filters.userFilter', ''),
            ],
        ]);
    }

    /**
     * Datatable
     *
     * @return mixed
     */
    public function datatable()
    {
        $table = request('table');
        $startDate = request('startDate');
        $endDate = request('endDate');
        $filters = request('filters');

        $data = $this->data_reports($table, $startDate, $endDate, $filters);

        if ($table === 'production_reports') {
            return DataTables::of($data)->addColumn('button', function ($data) {
                $url = route('queue.show', $data['id']);
                $disabled = $data['status'] == 'PENDENTE' ? 'disabled' : '';
                $classes = 'inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150';
                $classes .= $disabled
                    ? 'inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 cursor-not-allowed'
                    : 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150';
                return
                    '<a href="' . $url . '">
                        <button type="button" class="' . $classes . '" ' . $disabled . '>
                            ✓
                        </button>
                    </a>';
            })
                ->rawColumns(['button'])
                ->make(true);
        } else {
            return DataTables::of($data)->make(true);
        }
    }

    /**
     * Pega dados para relatórios
     *
     * @param string $table
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @return array
     */
    public function data_reports($table, $startDate, $endDate, $filters = [])
    {
        $teamId = Auth::user()->currentTeam->id;

        $query = Queue::with([
            'entry:id,unique_id,team_id',
            'user',
            'reports:queue_id,defect_solution_id,observation',
            'reports.defectSolution.component',
            'products',
            'productOutput.user'
        ])
            ->whereDate('updated_at', '>=', $startDate)
            ->whereDate('updated_at', '<=', $endDate)
            ->whereHas('entry', function ($q) use ($teamId) {
                $q->where('team_id', $teamId);
            });

        if (!empty($filters['productFilter'])) {
            $query->where('product_new', 'LIKE', '%' . $filters['productFilter']['label'] . '%');
        }
        if (!empty($filters['userFilter'])) {
            $query->where('user_id', '=', $filters['userFilter']['id']);
        }

        $outputs = $query->get();

        if ($table === 'reports') {
            $formatted = $outputs->flatMap(function ($output) {
                return $output->reports->take(2)->map(function ($report) use ($output) {
                    return [
                        'id' => $output->id,
                        'created_at' => $output->created_at->format('d/m/Y H:i:s'),
                        'user_name' => $output->user->name ?? null,
                        'product' => $output->product ?? null,
                        'product_new' => $output->product_new ?? null,
                        'transformed' => $output->product_new === $output->product ? 0 : 1,
                        'family' => $output->products->family ?? null,
                        'component' => $report->defectSolution?->component->component ?? null,
                        'defect' => $report->defectSolution?->defect ?? null,
                        'solution' => $report->defectSolution?->solution ?? null,
                        'serial_number' => $output->serial_number ?? null,
                        'lot' => $output->product_lot ?? null,
                        'observation' => $report->observation ?? null,
                        'updated_at' => $output->updated_at->format('d/m/Y H:i:s'),
                        'status' => $output->status ?? null,
                        'output_date' => $output->productOutput ? $output->productOutput->created_at->format('d/m/Y H:i:s') : null,
                        'output_user_name' => $output->productOutput->user->name ?? null,
                    ];
                });
            });
        } elseif ($table === 'daily_production_reports') {

            $categorias = [
                ['family' => ['TABLET'], 'label' => 'NB'],
                ['family' => ['SMARTPHONE'], 'label' => 'P9'],
                ['family' => ['FEATURE PHONE'], 'label' => 'BAR'],
                ['family' => ['PERSONAL COMPUTER'], 'label' => 'PC'],
                ['family' => ['ESPORTES', 'MOBILIDADE ELETRICA'], 'label' => 'ES'],
                ['family' => ['BABY', 'DRONE', 'HEALTH CARE', 'ELETROPORTATEIS', 'SEGURANCA', 'ELETRO BEAUTY', 'WEARABLE', 'FERRAMENTAS', 'AUTOMOTIVO', 'LIQUIDIFICADOR', 'VENTILADOR'], 'label' => 'PER'],
                ['family' => ['VIDEO', 'AUDIO', 'MIDIA'], 'label' => 'TV_VD'],
                ['family' => ['PEN DRIVE'], 'label' => 'PD'],
            ];

            $formatted = $outputs
                ->groupBy(fn($item) => Carbon::parse($item->updated_at)->format('d/m/Y'))
                ->flatMap(function ($groupByDate, $date) use ($categorias) {
                    return $groupByDate
                        ->groupBy(fn($item) => optional($item->user)->name ?? 'AGUARDANDO ATRIBUIÇÃO')
                        ->map(function ($group, $responsavel) use ($date, $categorias) {
                            $recuperados = $group->where('status', 'RECUPERADO');
                            $descartes = $group->where('status', 'DESCARTE');

                            $dados = [
                                'DATA' => $date,
                                'RESPONSAVEL' => $responsavel,
                                'DESCARTE' => $descartes->count(),
                                'TOTAL' => $recuperados->count(),
                            ];

                            foreach ($categorias as $categoria) {
                                $campo = $categoria['label'];
                                $familias = $categoria['family'];

                                $dados[$campo] = $recuperados->filter(function ($item) use ($familias) {
                                    return in_array(optional($item->products)->family, $familias);
                                })->count();
                            }

                            return $dados;
                        })->values();
                })->values();
        } elseif ($table === 'production_reports') {
            $formatted = $outputs->map(function ($output) {
                return [
                    'id' => $output->id,
                    'created_at' => $output->created_at->format('d/m/Y H:i:s'),
                    'user_name' => $output->user->name ?? null,
                    'product' => $output->product ?? null,
                    'product_new' => $output->product_new ?? null,
                    'transformed' => $output->product_new === $output->product ? 0 : 1,
                    'family' => $output->products->family ?? null,
                    'serial_number' => $output->serial_number ?? null,
                    'lot' => $output->product_lot ?? null,
                    'updated_at' => $output->updated_at->format('d/m/Y H:i:s'),
                    'status' => $output->status ?? null,
                    'output_date' => $output->productOutput?->created_at?->format('d/m/Y H:i:s'),
                    'output_user_name' => $output->productOutput->user->name ?? null,
                    'entry_id' => $output->entry->id ?? null,
                ];
            });
        }

        return $formatted;
    }

    public function export($table, $startDate, $endDate, $filters = [])
    {
        $info = $this->data_reports($table, $startDate, $endDate, $filters) ?? [];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($table === 'reports') {
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
                "Número de série",
                "Lote",
                "Observação",
                "Atualizado em",
                "Status",
                "Data Embalagem",
                "Responsável embalagem",
            ];

            foreach ($headers as $index => $header) {
                $col = chr(65 + $index);
                $sheet->setCellValue($col . '1', $header);
            }
            $rowIndex = 2;
            foreach ($info as $data) {
                $sheet->setCellValue('A' . $rowIndex, $data['id'] ?? "");
                $sheet->setCellValue('B' . $rowIndex, $data['created_at'] ?? "");
                $sheet->setCellValue('C' . $rowIndex, $data['user_name'] ?? "");
                $sheet->setCellValue('D' . $rowIndex, $data['product'] ?? "");
                $sheet->setCellValue('E' . $rowIndex, $data['product_new'] ?? "");
                $sheet->setCellValue('F' . $rowIndex, $data['transformed'] ?? "");
                $sheet->setCellValue('G' . $rowIndex, $data['family'] ?? "");
                $sheet->setCellValue('H' . $rowIndex, $data['component'] ?? "");
                $sheet->setCellValue('I' . $rowIndex, $data['defect'] ?? "");
                $sheet->setCellValue('J' . $rowIndex, $data['solution'] ?? "");
                $sheet->setCellValue('K' . $rowIndex, $data['serial_number'] ?? "");
                $sheet->setCellValue('L' . $rowIndex, $data['lot'] ?? "");
                $sheet->setCellValue('M' . $rowIndex, $data['observation'] ?? "");
                $sheet->setCellValue('N' . $rowIndex, $data['updated_at'] ?? "");
                $sheet->setCellValue('O' . $rowIndex, $data['status'] ?? "");
                $sheet->setCellValue('P' . $rowIndex, $data['output_date'] ?? "");
                $sheet->setCellValue('Q' . $rowIndex, $data['output_user_name'] ?? "");
                $rowIndex++;
            }
        } elseif ($table === 'production_reports') {
            $headers = [
                "ID",
                "Criado em",
                "Responsável",
                "Produto Entrada",
                "Produto Saída",
                "Transformação",
                "Família",
                "Número de série",
                "Lote",
                "Observação",
                "Atualizado em",
                "Status",
                "Data Embalagem",
                "Responsável embalagem",
            ];

            foreach ($headers as $index => $header) {
                $col = chr(65 + $index);
                $sheet->setCellValue($col . '1', $header);
            }
            $rowIndex = 2;
            foreach ($info as $data) {
                $sheet->setCellValue('A' . $rowIndex, $data['id'] ?? '');
                $sheet->setCellValue('B' . $rowIndex, $data['created_at'] ?? '');
                $sheet->setCellValue('C' . $rowIndex, $data['user_name'] ?? '');
                $sheet->setCellValue('D' . $rowIndex, $data['product'] ?? '');
                $sheet->setCellValue('E' . $rowIndex, $data['product_new'] ?? '');
                $sheet->setCellValue('F' . $rowIndex, $data['transformed'] ?? '');
                $sheet->setCellValue('G' . $rowIndex, $data['family'] ?? '');
                $sheet->setCellValue('H' . $rowIndex, $data['serial_number'] ?? '');
                $sheet->setCellValue('I' . $rowIndex, $data['lot'] ?? '');
                $sheet->setCellValue('J' . $rowIndex, $data['observation'] ?? '');
                $sheet->setCellValue('K' . $rowIndex, $data['updated_at'] ?? '');
                $sheet->setCellValue('L' . $rowIndex, $data['status'] ?? '');
                $sheet->setCellValue('M' . $rowIndex, $data['output_date'] ?? '');
                $sheet->setCellValue('N' . $rowIndex, $data['output_user_name'] ?? '');
                $rowIndex++;
            }
        } elseif ($table === 'daily_production_reports') {
            $headers = [
                "DATA",
                "RESPONSÁVEL",
                "DESCARTE",
                "TOTAL",
                "NB",
                "P9",
                "BAR",
                "PC",
                "ES",
                "PER",
                "TV/VD",
                "PD",
            ];

            foreach ($headers as $index => $header) {
                $col = chr(65 + $index);
                $sheet->setCellValue($col . '1', $header);
            }

            $rowIndex = 2;
            foreach ($info as $data) {
                $sheet->setCellValue('A' . $rowIndex, $data['DATA'] ?? "");
                $sheet->setCellValue('B' . $rowIndex, $data['RESPONSAVEL'] ?? "");
                $sheet->setCellValue('C' . $rowIndex, $data['DESCARTE'] ?? "");
                $sheet->setCellValue('D' . $rowIndex, $data['TOTAL'] ?? "");
                $sheet->setCellValue('E' . $rowIndex, $data['NB'] ?? "");
                $sheet->setCellValue('F' . $rowIndex, $data['P9'] ?? "");
                $sheet->setCellValue('G' . $rowIndex, $data['BAR'] ?? "");
                $sheet->setCellValue('H' . $rowIndex, $data['PC'] ?? "");
                $sheet->setCellValue('I' . $rowIndex, $data['ES'] ?? "");
                $sheet->setCellValue('J' . $rowIndex, $data['PER'] ?? "");
                $sheet->setCellValue('K' . $rowIndex, $data['TV_VD'] ?? "");
                $sheet->setCellValue('L' . $rowIndex, $data['PD'] ?? "");

                $rowIndex++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $filename = $table . '_' . date('YmdHis') . '.xlsx';
        $tempFile = sys_get_temp_dir() . '/' . $filename;
        $writer->save($tempFile);

        if (!file_exists($tempFile)) {
            abort(500, 'Falha ao criar o arquivo Excel.');
        }

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
