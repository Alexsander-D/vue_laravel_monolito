<?php

namespace App\Http\Controllers\External;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Registration\Products;
use App\Models\External\Screening;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningTimeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ProductEntryController extends Controller
{
    public function index(Request $request)
    {
        $screeningId = $request["screeningId"];

        $entries = ScreeningReport::select(
            DB::raw('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            DB::raw('COUNT(screening_report.id) as quantity'),
            DB::raw('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_id', $screeningId)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee'
            )

            ->get()
            ->map(function ($entry) {

                $entry->price = number_format($entry->price, 2, '.', '');
                return $entry;
            });

        $screening = Screening::with('customers')->find($screeningId);

        $companyName = $screening->customers->company_name ?? 'N/A';
        $state = $screening->customers->state ?? 'N/A';
        $city = $screening->customers->city ?? 'N/A';
        $screeningStatus = $screening->status ?? 'N/A';
        $typeService = $screening->type_service ?? 'N/A';

        return Inertia::render('ExternalService/CustomerService/ProductEntry', [
            'productsData' => [
                'entries' => $entries,
                'screeningId' => (int) $screeningId,
                'companyName' => $companyName,
                'state' => $state,
                'city' => $city,
                'screeningStatus' => $screeningStatus,
                'typeService' => $typeService,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'products_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'guarantee' => 'required|in:em garantia,fora de garantia',
            'screening_id' => 'required|exists:screening,id',
        ]);

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
                return response()->json([
                    'errors' => ['price' => 'O valor unitário deve ser igual ao já cadastrado para este produto na triagem.']
                ], 422);
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
                'price' => $validatedData['price'],
                'guarantee' => $validatedData['guarantee'],
            ]);
        }

        $product = Products::find($validatedData['products_id']);
        $description = "Foram cadastrados {$validatedData['quantity']} produto(s) com SKU: {$product->sku}";
        $route = route('ProductEntry.index', ['screeningId' => $validatedData['screening_id']]);
        $responsible = Auth::user()->name;

        ScreeningTimeline::create([
            'screening_id' => $validatedData['screening_id'],
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        $entries = ScreeningReport::select(
            DB::RAW('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            DB::RAW('count(screening_report.id) as quantity'),
            DB::RAW('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_id', $validatedData['screening_id'])
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
            )->get()
            ->map(function ($entry) {
                $entry->price = $entry->price !== null
                    ? number_format($entry->price, 2, '.', '')
                    : null;
                return $entry;
            });

        return response()->json([
            'success' => true,
            'message' => 'Produto(s) adicionado(s) com sucesso!',
            'entries' => $entries,
        ]);
    }

    public function excel(Request $request)
    {
        $validatedData = $request->validate([
            'rows' => 'required|array',
            'screening_id' => 'required|exists:screening,id',
        ]);

        $rows = $validatedData['rows'][0];
        $screeningId = $validatedData['screening_id'];
        $slicedRows = array_slice($rows, 1);

        $user = Auth::id();
        $invalidSkus = [];
        $logSkus = [];
        $incompleteRows = [];

        foreach ($slicedRows as $index => $row) {

            if (empty($row) || (count(array_filter($row, fn($v) => trim($v) !== '')) === 0)) {
                continue;
            }

            $sku = trim($row[0] ?? '');
            $quantity = $row[1] ?? null;
            $guarantee = $row[2] ?? null;
            $price = $row[3] ?? null;

            if ($sku === '' || $quantity === null || $guarantee === null || trim($quantity) === '' || trim($guarantee) === '') {
                $incompleteRows[] = $index + 2;
                continue;
            }

            try {
                $product = Products::where('sku', $sku)->firstOrFail();

                // 🔹 Trata valor unitário
                if ($price === '' || $price === null) {
                    $price = null;
                } else {
                    $price = floatval($price);
                    if ($price < 0) {
                        $price = null;
                    }
                }

                $existingEntries = ScreeningReport::where('screening_id', $screeningId)
                    ->where('products_id', $product->id)
                    ->get();

                $hasPriceDefined = $existingEntries->contains(fn($entry) => !is_null($entry->price));

                if ($hasPriceDefined) {
                    $firstDefinedPrice = $existingEntries->firstWhere('price', '!=', null)->price;
                    if (is_null($price) || $price != $firstDefinedPrice) {
                        $invalidSkus[] = "{$product->sku} (valor diferente do cadastrado)";
                        continue;
                    }
                }

                if (!$hasPriceDefined && !is_null($price)) {
                    ScreeningReport::where('screening_id', $screeningId)
                        ->where('products_id', $product->id)
                        ->whereNull('price')
                        ->update(['price' => $price]);
                }

                if (is_numeric($quantity) && $quantity > 0) {
                    for ($i = 0; $i < $quantity; $i++) {
                        ScreeningReport::create([
                            'user_id' => $user,
                            'screening_id' => $screeningId,
                            'products_id' => $product->id,
                            'guarantee' => $guarantee,
                            'price' => $price,
                        ]);
                    }

                    $logSkus[] = "{$quantity}x SKU: {$product->sku}";
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $invalidSkus[] = $sku;
            }
        }

        if (!empty($incompleteRows)) {
            return response()->json([
                'success' => false,
                'close_modal' => true,
                'message' => 'Existem linhas com campos obrigatórios vazios (PRODUTO, QTD. ou GARANTIA).',
                'incomplete_rows' => $incompleteRows,
            ], 422);
        }

        $responsible = Auth::user()->name;
        $route = route('ProductEntry.index', ['screeningId' => $screeningId]);
        $description = "Produtos cadastrados via Excel: " . implode(', ', $logSkus);

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
            DB::raw('COUNT(screening_report.id) as quantity'),
            DB::raw('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_id', $screeningId)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee'
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
            'message' => empty($invalidSkus)
                ? 'Todos os produtos foram cadastrados com sucesso.'
                : 'Importação concluída com avisos.',
            'entries' => $entries,
            'invalid_skus' => $invalidSkus,
        ], 200);
    }

    public function destroy($entryId)
    {
        $entry = ScreeningReport::findOrFail($entryId);
        $screeningId = $entry->screening_id;

        $product = Products::find($entry->products_id);
        $sku = $product ? $product->sku : 'SKU não encontrado';

        $entry->delete();

        $description = "Produto com SKU: {$sku} foi excluído.";
        $route = route('ProductEntry.index', ['screeningId' => $screeningId]);
        $responsible = Auth::user()->name;

        ScreeningTimeline::create([
            'screening_id' => $screeningId,
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        $entries = ScreeningReport::select(
            DB::RAW('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            DB::RAW('count(screening_report.id) as quantity')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_id', $screeningId)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
            )->get();

        return response()->json(['success' => true, 'message' => 'Registro excluído com sucesso.', 'entries' => $entries]);
    }

    public function destroyAll($screeningId)
    {
        ScreeningReport::where('screening_id', $screeningId)->delete();

        $description = "Todos os produtos excluídos para o ID: " . $screeningId;
        $route = route('ProductEntry.index', ['screeningId' => $screeningId]);
        $responsible = Auth::user()->name;


        ScreeningTimeline::create([
            'screening_id' => $screeningId,
            'description' => $description,
            'responsible' => $responsible,
            'route' => $route,
        ]);

        $entries = ScreeningReport::select(
            DB::RAW('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            DB::RAW('count(screening_report.id) as quantity')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_id', $screeningId)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee',
            )->get();

        return response()->json(['success' => true, 'message' => 'Registros excluídos com sucesso.', 'entries' => $entries]);
    }

    public function finalize($id)
    {
        $screening = Screening::findOrFail($id);

        if ($screening->type_service !== 'pre-agenda') {

            $screening->status = 'aguardando agendamento';
            $screening->save();

            $description = "Status da triagem ID {$id} alterado para AGUARDANDO AGENDAMENTO.";
            $route = route('ViewScreening.index', ['screeningId' => $id]);
            $responsible = Auth::user()->name;

            ScreeningTimeline::create([
                'screening_id' => $id,
                'description' => $description,
                'responsible' => $responsible,
                'route' => $route,
            ]);
        }

        return response()->json(['success' => 'Triagem finalizada com sucesso.']);
    }

    public function datatable($screeningId)
    {
        $query = ScreeningReport::select(
            DB::raw('MIN(screening_report.id) as id'),
            'screening_report.screening_id',
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            DB::raw('COUNT(screening_report.id) as quantity'),
            DB::raw('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_report.screening_id', $screeningId)
            ->groupBy(
                'screening_report.screening_id',
                'products.sku',
                'products.family',
                'screening_report.guarantee'
            );

        return DataTables::of($query)
            ->editColumn('sku', function ($row) {
                $color = strtolower($row->guarantee) === 'fora de garantia'
                    ? 'bg-red-600 text-white p-1 rounded'
                    : 'text-gray-800 dark:text-gray-200';
                return "<span class='{$color}'>" . strtoupper($row->sku ?? '-') . "</span>";
            })
            ->editColumn('family', fn($row) => strtoupper($row->family ?? '-'))
            ->editColumn('guarantee', fn($row) => strtoupper($row->guarantee ?? '-'))
            ->editColumn('quantity', fn($row) => (int) $row->quantity)
            ->editColumn('price', fn($row) => number_format($row->price ?? 0, 2, ',', '.'))
            ->addColumn('button', function ($row) {
                return '
            <button type="button" data-id="' . $row->id . '"
                class="delete-btn flex justify-center items-center gap-2 size-[38px] text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 focus:outline-none"
                title="Excluir registro">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        ';
            })
            ->rawColumns(['sku', 'button'])
            ->make(true);
    }

    public function export($screeningId)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ["Produto", "Família", "Garantia", "Quantidade", "Valor Unitário"];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue(chr(65 + $i) . '1', $header);
            $sheet->getColumnDimension(chr(65 + $i))->setAutoSize(true);
        }

        $rows = ScreeningReport::select(
            'products.sku',
            'products.family',
            'screening_report.guarantee',
            DB::raw('COUNT(screening_report.id) as quantity'),
            DB::raw('AVG(screening_report.price) as price')
        )
            ->leftJoin('products', 'products.id', '=', 'screening_report.products_id')
            ->where('screening_report.screening_id', $screeningId)
            ->groupBy('products.sku', 'products.family', 'screening_report.guarantee')
            ->get();

        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A$r", $row->sku);
            $sheet->setCellValue("B$r", $row->family);
            $sheet->setCellValue("C$r", strtoupper($row->guarantee));
            $sheet->setCellValue("D$r", $row->quantity);
            $sheet->setCellValue("E$r", number_format($row->price, 2, ',', '.'));
            $r++;
        }

        $file = tempnam(sys_get_temp_dir(), 'entries') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        return response()->download($file, 'entries_' . $screeningId . '.xlsx')->deleteFileAfterSend(true);
    }
}
