<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
class ViewScreeningController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status') ?: null;

        // datas padrão (mês atual)
        $startDate = $request->input('startDate') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->input('endDate') ?? now()->endOfMonth()->format('Y-m-d');

        // Widgets
        $counts = DB::table('screening')
            ->selectRaw("
                SUM(CASE WHEN status = 'aguardando produtos' THEN 1 ELSE 0 END) AS `AGUARDANDO PRODUTOS`,
                SUM(CASE WHEN status = 'aguardando agendamento' THEN 1 ELSE 0 END) AS `AGUARDANDO AGENDAMENTO`,
                SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) AS `AGENDADA`,
                SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) AS `CONFIRMADA`,
                SUM(CASE WHEN status = 'finalizada' THEN 1 ELSE 0 END) AS `FINALIZADA`,
                SUM(CASE WHEN status = 'laudo aprovado' THEN 1 ELSE 0 END) AS `LAUDO APROVADO`,
                SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) AS `CANCELADA`,
                COUNT(*) AS `TOTAL`
            ")
            ->first();

        $customersInfo = DB::table('customers')
            ->select(
                'screening.id',
                'customers.company_name',
                'screening.type_service',
                'screening.created_at',
                'screening.status'
            )
            ->join('screening', 'customers.id', '=', 'screening.customers_id')
            ->when($status, function ($query) use ($status) {
                return $query->where('screening.status', strtolower($status));
            })
            ->get();

        $formattedCounts = [
            ['title' => 'aguardando produtos', 'value' => $counts->{'AGUARDANDO PRODUTOS'} ?? 0, 'status' => 'aguardando produtos', 'route' => 'ViewScreening.index', 'filter' => ['status' => 'aguardando produtos']],
            ['title' => 'aguardando agendamento', 'value' => $counts->{'AGUARDANDO AGENDAMENTO'} ?? 0, 'status' => 'aguardando agendamento', 'route' => 'ViewScreening.index', 'filter' => ['status' => 'aguardando agendamento']],
            ['title' => 'agendada', 'value' => $counts->AGENDADA ?? 0, 'status' => 'agendada', 'route' => 'ViewScreening.index', 'filter' => ['status' => 'agendada']],
            ['title' => 'confirmada', 'value' => $counts->CONFIRMADA ?? 0, 'status' => 'confirmada', 'route' => 'ViewScreening.index', 'filter' => ['status' => 'confirmada']],
            ['title' => 'finalizada', 'value' => $counts->FINALIZADA ?? 0, 'status' => 'finalizada', 'route' => 'ViewScreening.index', 'filter' => ['status' => 'finalizada']],
            ['title' => 'laudo aprovado', 'value' => $counts->{'LAUDO APROVADO'} ?? 0, 'status' => 'laudo aprovado', 'route' => 'ViewScreening.index', 'filter' => ['status' => 'laudo aprovado']],
            ['title' => 'cancelada', 'value' => $counts->CANCELADA ?? 0, 'status' => 'cancelada', 'route' => 'ViewScreening.index', 'filter' => ['status' => 'cancelada']],
            ['title' => 'total', 'value' => $counts->TOTAL ?? 0, 'status' => 'total', 'route' => 'ViewScreening.index'],
        ];

        return Inertia::render('ExternalService/CustomerService/ViewScreening', [
            'customersInfo' => $customersInfo,
            'widgets' => $formattedCounts,
            'selectedStatus' => $status,
            'date' => [
                'startDate' => $startDate,
                'endDate'   => $endDate,
            ],
        ]);
    }

    public function datatable()
    {
        $status     = request()->get('status');
        $startDate  = request()->get('startDate');
        $endDate    = request()->get('endDate');

        $query = DB::table('customers')
            ->join('screening', 'customers.id', '=', 'screening.customers_id')
            ->select(
                'screening.id',
                'customers.company_name',
                'screening.type_service',
                'screening.created_at',
                'screening.status'
            );

        // filtro status
        if ($status && $status !== 'total') {
            $query->where('screening.status', strtolower($status));
        }

        // filtro de data
        if ($startDate && $endDate) {
            $query->whereBetween('screening.created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]);
        }

        return DataTables::of($query->get())
            ->addColumn('button', function ($row) {
                $status = strtolower($row->status);

                $route = match ($status) {
                    'aguardando produtos'     => route('ProductEntry.index', ['screeningId' => $row->id]),
                    'aguardando agendamento'  => route('customers.scheduling', ['screeningId' => $row->id]),
                    default                   => route('customers.finalReport', ['screeningId' => $row->id]),
                };

                return <<<HTML
<a href="{$route}">
    <button type="button" class="flex justify-center items-center gap-2 size-[38px] rounded-lg bg-blue-600 text-white hover:bg-blue-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M21 21l-4.35-4.35m1.7-5.9a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
        </svg>
    </button>
</a>
HTML;
            })
            ->editColumn('created_at', fn($row) => \Carbon\Carbon::parse($row->created_at)->format('d/m/y'))
            ->editColumn('type_service', fn($row) => strtoupper($row->type_service))
            ->editColumn('status', fn($row) => strtoupper($row->status))
            ->rawColumns(['button'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $status = $request->get('status');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            "ID",
            "Nome do Cliente",
            "Tipo de Serviço",
            "Data de Criação",
            "Status",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        $query = DB::table('customers')
            ->join('screening', 'customers.id', '=', 'screening.customers_id')
            ->select(
                'screening.id',
                'customers.company_name',
                'screening.type_service',
                'screening.created_at',
                'screening.status'
            );

        if ($status && $status !== 'total') {
            $query->where('screening.status', strtolower($status));
        }

        $rows = $query->get();

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowIndex, $row->id);
            $sheet->setCellValue('B' . $rowIndex, $row->company_name);
            $sheet->setCellValue('C' . $rowIndex, strtoupper($row->type_service));
            $sheet->setCellValue('D' . $rowIndex, \Carbon\Carbon::parse($row->created_at)->format('d/m/Y'));
            $sheet->setCellValue('E' . $rowIndex, strtoupper($row->status));
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'screening_' . ($status ?: 'todos') . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

}
