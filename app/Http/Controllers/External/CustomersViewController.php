<?php

namespace App\Http\Controllers\External;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\External\Customers;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class CustomersViewController extends Controller
{
    
    public function show()
    {
        return Inertia::render('ExternalService/CustomerService/ViewCustomers', [
            'customersInfo' => Customers::all(),
        ]);
    }

    public function datatable()
    {
        $data = Customers::select([
            'id',
            'type_person',
            'company_name',
            'trade_name',
            'cep',
            'state',
            'city',
            'road',
            'district',
            'number',
            'telephone',
            'email',
            'responsible',
            'observation',
        ]);

        return DataTables::of($data)
            ->addColumn('button', function ($row) {
                $url = route('customer.show', $row->id);

                return <<<HTML
                    <a href="{$url}">
                        <button type="button"
                            class="flex justify-center items-center size-[38px] rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            <svg class="shrink-0 size-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z">
                                </path>
                            </svg>
                        </button>
                    </a>
                HTML;
            })
            ->rawColumns(['button'])
            ->make(true);
    }

    public function destroy(Request $request): void
    {
        Customers::find($request['customerId'])->delete();
    }

    public function export(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Busca dos dados
        $query = Customers::query();

        if ($request->has('filters')) {
            $filters = $request->input('filters');

            if (!empty($filters['city'])) {
                $query->where('city', 'like', '%' . $filters['city'] . '%');
            }

            if (!empty($filters['state'])) {
                $query->where('state', 'like', '%' . $filters['state'] . '%');
            }

            if (!empty($filters['company_name'])) {
                $query->where('company_name', 'like', '%' . $filters['company_name'] . '%');
            }
        }

        $customers = $query->get([
            'id',
            'type_person',
            'company_name',
            'trade_name',
            'cep',
            'state',
            'city',
            'road',
            'district',
            'number',
            'telephone',
            'email',
            'responsible',
            'observation'
        ]);

        // Cabeçalhos
        $headers = [
            "ID",
            "Tipo de Pessoa",
            "Razão Social",
            "Nome Fantasia",
            "CEP",
            "Estado",
            "Cidade",
            "Rua",
            "Bairro",
            "Número",
            "Telefone",
            "E-mail",
            "Responsável",
            "Observação",
        ];

        // Escreve cabeçalhos na planilha
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        // Escreve dados
        $rowIndex = 2;
        foreach ($customers as $customer) {
            $sheet->setCellValue('A' . $rowIndex, $customer->id);
            $sheet->setCellValue('B' . $rowIndex, $customer->type_person);
            $sheet->setCellValue('C' . $rowIndex, $customer->company_name);
            $sheet->setCellValue('D' . $rowIndex, $customer->trade_name);
            $sheet->setCellValue('E' . $rowIndex, $customer->cep);
            $sheet->setCellValue('F' . $rowIndex, $customer->state);
            $sheet->setCellValue('G' . $rowIndex, $customer->city);
            $sheet->setCellValue('H' . $rowIndex, $customer->road);
            $sheet->setCellValue('I' . $rowIndex, $customer->district);
            $sheet->setCellValue('J' . $rowIndex, $customer->number);
            $sheet->setCellValue('K' . $rowIndex, $customer->telephone);
            $sheet->setCellValue('L' . $rowIndex, $customer->email);
            $sheet->setCellValue('M' . $rowIndex, $customer->responsible);
            $sheet->setCellValue('N' . $rowIndex, $customer->observation);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'clientes_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

}


