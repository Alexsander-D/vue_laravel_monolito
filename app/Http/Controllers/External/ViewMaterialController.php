<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Screening;
use App\Models\External\Customers;
use App\Models\External\Material;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningTimeline;
use App\Models\External\TechnicalScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ViewMaterialController extends Controller
{
    public function index()
    {
        $screenings = Screening::all();

        $productsGrouped = ScreeningReport::with('products')
            ->whereIn('screening_id', $screenings->pluck('id'))
            ->get()
            ->groupBy('screening_id')
            ->map(fn($screeningReports) => $screeningReports->flatMap(fn($report) => $report->products));

        $materials = Material::whereIn('screening_id', $screenings->pluck('id'))->get();

        $materialsData = [
            'screenings' => $screenings,
            'productsGrouped' => $productsGrouped,
            'materials' => $materials,
        ];

        return Inertia::render(
            'ExternalService/CustomerService/ViewMaterial',
            [
                'materialsData' => $materialsData,
            ]
        );
    }

    public function show($screeningId)
    {
        $screening = Screening::with(['materials', 'customers'])->findOrFail($screeningId);
        $customer = Customers::whereHas('screenings', fn($query) => $query->where('id', $screeningId))->first();
        $technicians = TechnicalScale::where('screening_id', $screeningId)
            ->join('users', 'technical_scales.user_id', '=', 'users.id')
            ->select('users.id', 'users.name')
            ->get();

        $productsGrouped = ScreeningReport::getProductsGroupedBySku($screeningId);

        $materials = Material::where('screening_id', $screeningId)->get()->map(function ($material) {
            $material->is_finalizado = in_array($material->status, ['Cancelado', 'Entregue']);
            return $material;
        });

        $materialsData = [
            'screenings' => [$screening],
            'customersInfo' => $customer ? [$customer] : [],
            'technicians' => $technicians,
            'productsGrouped' => $productsGrouped,
            'materials' => $materials,
        ];

        return Inertia::render('ExternalService/CustomerService/Material', [
            'materialsData' => $materialsData,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'screening_id' => 'required|exists:screening,id',
            'deadline_list' => 'required|date',
            'material_output' => 'required|date',
            'expected_arrival' => 'required|date',
            'type_transport' => 'nullable',
            'status' => 'required',
            'nf' => 'nullable|string|max:255',
            'observation' => 'nullable|string',
        ];

        $messages = [
            'screening_id.required' => 'O campo de triagem é obrigatório.',
            'screening_id.exists' => 'A triagem selecionada não existe.',
            'deadline_list.required' => 'O prazo da lista é obrigatório.',
            'deadline_list.date' => 'O prazo da lista deve ser uma data válida.',
            'material_output.required' => 'A saída do material é obrigatória.',
            'material_output.date' => 'A saída do material deve ser uma data válida.',
            'expected_arrival.required' => 'A data de chegada esperada é obrigatória.',
            'expected_arrival.date' => 'A data de chegada esperada deve ser uma data válida.',
            'status.required' => 'O status é obrigatório.',
            'nf.string' => 'A nota fiscal deve ser um texto.',
            'nf.max' => 'A nota fiscal não pode ultrapassar 255 caracteres.',
        ];

        $validated = $request->validate($rules, $messages);

        $material = Material::create($validated);

        $screening = Screening::findOrFail($validated['screening_id']);
        $description = "Material ID: {$material->id} atualizado para o status: {$material->status}. Cliente: {$screening->customers->company_name}";
        $route = route('viewMaterial.show', ['screeningId' => $screening->id]);
        $responsible = Auth::user()->name;
        ScreeningTimeline::create([
            'screening_id' => $screening->id,
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        return Redirect::route('viewMaterial.show', ['screeningId' => $screening->id])
            ->with('success', 'Material cadastrado com sucesso!');
    }


    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'screening_id' => 'required|exists:screening,id',
            'deadline_list' => 'required|date',
            'material_output' => 'required|date',
            'expected_arrival' => 'required|date',
            'type_transport' => 'nullable',
            'status' => 'required|string',
            'nf' => 'nullable|string|max:255',
            'observation' => 'nullable|string',

        ]);

        $material = Material::findOrFail($id);
        $originalData = $material->getOriginal();

        $material->update($validated);

        $screening = Screening::findOrFail($validated['screening_id']);
        $responsible = Auth::user()->name;

        $fieldTranslations = [
            'deadline_list' => 'Prazo da lista',
            'material_output' => 'Data de saída do material',
            'expected_arrival' => 'Previsão de chegada',
            'type_transport' => 'Tipo de transporte',
            'status' => 'Status',
            'nf' => 'Nota Fiscal',
            'observation' => 'Observações',
        ];

        $changes = [];
        foreach ($validated as $key => $value) {
            if ($originalData[$key] != $value) {
                $fieldName = $fieldTranslations[$key] ?? ucfirst(str_replace('_', ' ', $key));
                $changes[] = "{$fieldName}: '{$originalData[$key]}' → '{$value}'";
            }
        }

        $description = "Material atualizado para a triagem do cliente: " . $screening->customers->company_name;
        if (!empty($changes)) {
            $description .= " | Alterações: " . implode(', ', $changes);
        }

        $route = route('viewMaterial.show', ['screeningId' => $screening->id]);

        ScreeningTimeline::create([
            'screening_id' => $screening->id,
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        return Redirect::route('viewMaterial.show', ['screeningId' => $screening->id])
            ->with('success', 'Material atualizado com sucesso!');
    }
    public function datatable()
    {
        $startDate = request()->get('startDate');
        $endDate   = request()->get('endDate');

        $query = Screening::query()
            ->leftJoin('materials', 'materials.screening_id', '=', 'screening.id')
            ->join('customers', 'customers.id', '=', 'screening.customers_id')
            ->select(
                'screening.id',
                'screening.status as triagem_status',
                'materials.status as material_status',
                'screening.service_start',
                'materials.material_output',
                'materials.type_transport',
                'customers.company_name',
                'screening.city',
                'screening.state'
            );

        if ($startDate && $endDate) {
            $query->whereBetween('screening.service_start', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]);
        }

        return DataTables::of($query->get())

            ->editColumn(
                'service_start',
                fn($row) =>
                $row->service_start ? \Carbon\Carbon::parse($row->service_start)->format('d/m/Y') : ''
            )

            ->editColumn(
                'material_output',
                fn($row) =>
                $row->material_output ? \Carbon\Carbon::parse($row->material_output)->format('d/m/Y') : ''
            )

            ->editColumn('triagem_status', fn($row) => strtoupper($row->triagem_status))
            ->editColumn('material_status', fn($row) => strtoupper($row->material_status))
            ->editColumn('type_transport', fn($row) => strtoupper($row->type_transport))

            ->addColumn('button', function ($row) {

                $icon = '
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-4.35-4.35m1.7-5.9a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                </svg>';

                if (strtolower($row->triagem_status) === "cancelada") {
                    return <<<HTML
<button type="button"
    onclick="showAlertIfCanceled()"
    class="size-[38px] flex items-center justify-center rounded-lg bg-gray-500 text-white">
    {$icon}
</button>
HTML;
                }

                $route = route('viewMaterial.show', $row->id);

                return <<<HTML
<a href="{$route}">
    <button type="button"
        class="size-[38px] flex items-center justify-center rounded-lg bg-blue-600 text-white hover:bg-blue-700">
        {$icon}
    </button>
</a>
HTML;
            })

            ->rawColumns(['button'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $startDate = $request->get('startDate');
        $endDate   = $request->get('endDate');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $headers = [
            "ID",
            "Status Triagem",
            "Status Material",
            "Início da Triagem",
            "Saída do Material",
            "Tipo Transporte",
            "Cliente",
            "Cidade",
            "Estado",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        // Query base
        $query = DB::table('screening')
            ->leftJoin('materials', 'materials.screening_id', '=', 'screening.id')
            ->select(
                'screening.id',
                'screening.status as triagem_status',
                'materials.status as material_status',
                'screening.service_start',
                'materials.material_output',
                'materials.type_transport',
                'screening.company_name',
                'screening.city',
                'screening.state'
            );

        if ($startDate && $endDate) {
            $query->whereBetween('screening.service_start', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]);
        }

        $rows = $query->get();

        $rowIndex = 2;

        foreach ($rows as $row) {

            $sheet->setCellValue('A' . $rowIndex, $row->id);
            $sheet->setCellValue('B' . $rowIndex, strtoupper($row->triagem_status ?? ''));
            $sheet->setCellValue('C' . $rowIndex, strtoupper($row->material_status ?? ''));

            $sheet->setCellValue(
                'D' . $rowIndex,
                $row->service_start
                    ? \Carbon\Carbon::parse($row->service_start)->format('d/m/Y')
                    : ''
            );

            $sheet->setCellValue(
                'E' . $rowIndex,
                $row->material_output
                    ? \Carbon\Carbon::parse($row->material_output)->format('d/m/Y')
                    : ''
            );

            $sheet->setCellValue('F' . $rowIndex, strtoupper($row->type_transport ?? ''));
            $sheet->setCellValue('G' . $rowIndex, $row->company_name);
            $sheet->setCellValue('H' . $rowIndex, $row->city);
            $sheet->setCellValue('I' . $rowIndex, $row->state);

            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);

        $filename = 'materiais_export_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
