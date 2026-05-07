<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\Registration\Products;
use App\Models\External\Screening;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet; // ✅ Geração de Excel
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class IncludeController extends Controller
{
    public function index()
    {
        $customersInfo = Screening::select('id', 'status', 'service_start', 'company_name', 'state', 'city')
            ->whereIn('status', ['agendada', 'confirmada'])
            ->get();

        return Inertia::render('ExternalService/CustomerService/ViewInclude', [
            'customersInfo' => $customersInfo
        ]);
    }

    public function datatable($screening_id)
    {
        $entries = ScreeningReport::select(
            DB::raw('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            'screening_report.include',
            DB::raw('count(screening_report.id) as quantity'),
            DB::raw('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_report.screening_id', $screening_id)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
                'screening_report.include'
            )
            ->get()
            ->map(function ($entry) {

                $entry->price = $entry->price !== null
                    ? number_format($entry->price, 2, '.', '')
                    : null;
                return $entry;
            });

        return DataTables::of($entries)
            ->make(true);
    }

    public function includeExport($screening_id)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 🧾 Cabeçalhos da planilha
        $headers = [
            "ID",
            "SKU do Produto",
            "Família",
            "Garantia",
            "Incluso",
            "Quantidade",
            "Preço Médio (R$)",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        // 📊 Consulta (mesma usada no datatable)
        $entries = ScreeningReport::select(
            DB::raw('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            'screening_report.include',
            DB::raw('COUNT(screening_report.id) as quantity'),
            DB::raw('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_report.screening_id', $screening_id)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
                'screening_report.include'
            )
            ->get()
            ->map(function ($entry) {
                $entry->price = $entry->price !== null
                    ? number_format($entry->price, 2, '.', '')
                    : null;
                return $entry;
            });

        // ✍️ Preenchendo a planilha
        $rowIndex = 2;
        foreach ($entries as $entry) {
            $sheet->setCellValue('A' . $rowIndex, $entry->id);
            $sheet->setCellValue('B' . $rowIndex, $entry->sku ?? '-');
            $sheet->setCellValue('C' . $rowIndex, $entry->family ?? '-');
            $sheet->setCellValue('D' . $rowIndex, ucfirst($entry->guarantee ?? 'N/A'));
            $sheet->setCellValue('E' . $rowIndex, ucfirst($entry->include ?? 'N/A'));
            $sheet->setCellValue('F' . $rowIndex, $entry->quantity);
            $sheet->setCellValue('G' . $rowIndex, $entry->price);
            $rowIndex++;
        }

        // 💾 Gerando o arquivo temporário
        $writer = new Xlsx($spreadsheet);
        $filename = 'includes_screening_' . $screening_id . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        // 📤 Retornando o download
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }


    public function screeningsDatatable()
    {
        $screenings = Screening::with('customers')
            ->whereIn('status', ['agendada', 'confirmada'])
            ->select('id', 'status', 'service_start', 'customers_id');

        return DataTables::of($screenings)
            ->addColumn('status', fn($screening) => strtoupper($screening->status ?? 'N/A'))
            ->addColumn('uniqueId', fn($screening) => $screening->id)
            ->addColumn('service_start', fn($screening) =>
            $screening->service_start ? Carbon::parse($screening->service_start)->format('d/m/y') : 'N/A')
            ->addColumn('company_name', fn($screening) =>
            strtoupper($screening->customers->company_name ?? 'N/A'))
            ->addColumn('city', fn($screening) =>
            strtoupper($screening->customers->city ?? 'N/A'))
            ->addColumn('state', fn($screening) =>
            strtoupper($screening->customers->state ?? 'N/A'))
            ->addColumn('button', function ($screening) {
                $routeName = route('include.show', $screening->id);
                $searchIcon = '
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.7-5.9a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                </svg>
            ';
                return '
                <a href="' . $routeName . '">
                    <button type="button" class="flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        ' . $searchIcon . '
                    </button>
                </a>
            ';
            })
            ->rawColumns(['button'])
            ->make(true);
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $headers = [
            "ID",
            "Status",
            "Data de Início",
            "Nome do Cliente",
            "Cidade",
            "Estado",
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        // Busca dos dados (mesmo filtro usado em screeningsDatatable)
        $screenings = Screening::with('customers')
            ->whereIn('status', ['agendada', 'confirmada'])
            ->select('id', 'status', 'service_start', 'customers_id')
            ->get();

        $rowIndex = 2;

        foreach ($screenings as $screening) {
            $sheet->setCellValue('A' . $rowIndex, $screening->id);
            $sheet->setCellValue('B' . $rowIndex, strtoupper($screening->status ?? 'N/A'));
            $sheet->setCellValue(
                'C' . $rowIndex,
                $screening->service_start
                    ? Carbon::parse($screening->service_start)->format('d/m/Y')
                    : 'N/A'
            );
            $sheet->setCellValue('D' . $rowIndex, strtoupper($screening->customers->company_name ?? 'N/A'));
            $sheet->setCellValue('E' . $rowIndex, strtoupper($screening->customers->city ?? 'N/A'));
            $sheet->setCellValue('F' . $rowIndex, strtoupper($screening->customers->state ?? 'N/A'));
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'triagens_agendadas_confirmadas_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }


    public function show($screening_id)
    {
        $screening = Screening::where('id', $screening_id)
            ->whereIn('status', ['agendada', 'confirmada'])
            ->with('customers')
            ->firstOrFail();

        $companyName = $screening->customers->company_name ?? 'N/A';
        $state = $screening->customers->state ?? 'N/A';
        $city = $screening->customers->city ?? 'N/A';
        $screeningStatus = $screening->status ?? 'N/A';
        $typeService = $screening->type_service ?? 'N/A';

        $entries = ScreeningReport::select(
            DB::raw('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            'screening_report.include',
            DB::raw('count(screening_report.id) as quantity')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_report.screening_id', $screening_id)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
                'screening_report.include'
            )
            ->get();


        return Inertia::render('ExternalService/CustomerService/Include', [
            'dataInclude' => [
                'screening' => $screening,
                'entries' => $entries,
                'screeningId' => (int) $screening_id,
                'companyName' => $companyName,
                'state' => $state,
                'city' => $city,
                'screeningStatus' => $screeningStatus,
                'typeService' => $typeService,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate(
            [
                'products_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'guarantee' => 'required|in:em garantia,fora de garantia',
                'screening_id' => 'required|exists:screening,id',
                'include' => 'required|string',
                'price' => 'nullable|numeric|min:0',
            ],
            [
                'products_id.required' => 'A seleção do produto é obrigatória.',
                'quantity.required' => 'A quantidade é obrigatória.',
                'guarantee.required' => 'O campo garantia é obrigatório.',
                'guarantee.in' => 'O campo garantia deve ser "em garantia" ou "fora de garantia".',
                'screening_id.required' => 'O campo triagem é obrigatório.',
                'include.required' => 'O valor include é obrigatório.',
                'price.numeric' => 'O preço deve ser um número válido.',
                'price.min' => 'O preço não pode ser negativo.',
            ]
        );

        $existingEntries = ScreeningReport::where('screening_id', $validatedData['screening_id'])
            ->where('products_id', $validatedData['products_id'])
            ->get();

        $newPrice = $validatedData['price'] ?? null;
        $hasPriceDefined = $existingEntries->contains(function ($entry) {
            return !is_null($entry->price);
        });

        if ($hasPriceDefined) {
            $firstDefinedPrice = $existingEntries->firstWhere('price', '!=', null)->price;

            if (is_null($newPrice) || $newPrice != $firstDefinedPrice) {
                return back()->withErrors(['price' => 'O valor unitário deve ser igual ao já cadastrado para este produto na triagem.'])->withInput();
            }
        }

        if (!$hasPriceDefined && !is_null($newPrice)) {
            ScreeningReport::where('screening_id', $validatedData['screening_id'])
                ->where('products_id', $validatedData['products_id'])
                ->whereNull('price')
                ->update(['price' => $newPrice]);
        }

        $user = Auth::id();

        for ($i = 0; $i < $validatedData['quantity']; $i++) {
            ScreeningReport::create([
                'user_id' => $user,
                'screening_id' => $validatedData['screening_id'],
                'products_id' => $validatedData['products_id'],
                'quantity' => 1,
                'guarantee' => $validatedData['guarantee'],
                'include' => $validatedData['include'],
                'price' => $newPrice,
            ]);
        }

        $screening = Screening::find($validatedData['screening_id']);
        $description = "Include cadastrado para o cliente: " . $screening->customers->company_name;
        $route = route('include.show', ['screening_id' => $validatedData['screening_id']]);
        $responsible = Auth::user()->name;

        ScreeningTimeline::create([
            'screening_id' => $validatedData['screening_id'],
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        return redirect()->route('include.show', ['screening_id' => $validatedData['screening_id']]);
    }

    public function destroyFromInclude($entryId)
    {
        $entry = ScreeningReport::findOrFail($entryId);
        $screeningId = $entry->screening_id;

        $product = Products::find($entry->products_id);
        $sku = $product ? $product->sku : 'SKU não encontrado';

        $entry->delete();

        $description = "Produto com SKU: {$sku} foi excluído via IncludeController.";
        $route = route('include.index', ['screeningId' => $screeningId]);
        $responsible = Auth::user()->name;

        ScreeningTimeline::create([
            'screening_id' => $screeningId,
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        $entries = ScreeningReport::select(
            DB::raw('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            'screening_report.include',
            DB::raw('count(screening_report.id) as quantity')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_report.screening_id', $screeningId)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
                'screening_report.include'
            )
            ->get();


        return response()->json([
            'success' => true,
            'message' => 'Registro excluído com sucesso.',
            'entries' => $entries
        ]);
    }

    public function destroyAllFromInclude($screeningId)
    {
        $entriesToDelete = ScreeningReport::where('screening_id', $screeningId)
            ->where('include', 'sim')
            ->get();

        $skus = [];

        foreach ($entriesToDelete as $entry) {
            $product = Products::find($entry->products_id);
            if ($product) {
                $skus[] = $product->sku;
            }
            $entry->delete();
        }

        $description = "Todos os produtos com include = 'sim' foram excluídos da triagem ID {$screeningId}. SKUs: " . implode(', ', $skus);
        $route = route('include.index', ['screeningId' => $screeningId]);
        $responsible = Auth::user()->name;

        ScreeningTimeline::create([
            'screening_id' => $screeningId,
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        $entries = ScreeningReport::select(
            DB::raw('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            'screening_report.include',
            DB::raw('count(screening_report.id) as quantity'),
            DB::raw('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_report.screening_id', $screeningId)
            ->whereNotNull('screening_report.include')
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
                'screening_report.include'
            )
            ->get()
            ->map(function ($entry) {
                $entry->price = $entry->price !== null
                    ? number_format($entry->price, 2, '.', '')
                    : null;
                return $entry;
            });

        return response()->json([
            'success' => true,
            'message' => 'Registros com include = "sim" excluídos com sucesso.',
            'entries' => $entries,
        ]);
    }
}
