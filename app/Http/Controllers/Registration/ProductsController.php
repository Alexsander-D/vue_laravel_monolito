<?php

namespace App\Http\Controllers\Registration;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Registration\Products;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Controller responsavel pelas operacoes de produtos
 */
class ProductsController extends Controller
{
    /**
     * Mostra a tela de produtos
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Registration/Products/Show');
    }

    /**
     * Retorna os produtos em formato de Datatable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function datatable()
    {
        return DataTables::of(Products::query())
            ->addColumn('action', function ($product) {
                $data = htmlspecialchars(json_encode([
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'ean' => $product->ean,
                    'family' => $product->family,
                    'description' => $product->description,
                    'customization' => $product->customization,
                ]), ENT_QUOTES, 'UTF-8');

                return
                    '<button type="button" class="flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" onclick="window.dispatchEvent(new CustomEvent(\'open-product-modal\', { detail: ' . $data . ' }))">
                        <svg class="shrink-0 size-6" fill="#fff" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 485.69 485.69">
                                <path d="M410.428,34.738h-76.405l5.155,23.852c0.634,2.961,0.603,5.934,0.271,8.859h54.621V452.98H91.588V67.449h54.637 c-0.332-2.941-0.348-5.914,0.285-8.891l5.156-23.82H75.248c-9.031,0-16.34,7.324-16.34,16.354v418.243 c0,9.016,7.309,16.354,16.34,16.354h335.18c9.031,0,16.354-7.341,16.354-16.354V51.093 C426.783,42.062,419.459,34.738,410.428,34.738z"></path>
                                <path d="M355.568,152.949h-111.71c-9.047,0-16.355,7.324-16.355,16.34c0,9.035,7.309,16.355,16.355,16.355h111.71 c9.047,0,16.354-7.32,16.354-16.355C371.924,160.273,364.615,152.949,355.568,152.949z"></path>
                                <path d="M355.568,253.254h-111.71c-9.047,0-16.355,7.323-16.355,16.354c0,9.021,7.309,16.357,16.355,16.357h111.71 c9.047,0,16.354-7.34,16.354-16.357C371.924,260.577,364.615,253.254,355.568,253.254z"></path>
                                <path d="M119.556,156.792c-6.898,5.82-7.786,16.137-1.965,23.047l23.855,28.27c3.117,3.699,7.688,5.805,12.496,5.805 c0.398,0,0.792-0.016,1.203-0.047c5.219-0.379,9.949-3.258,12.703-7.719l42.914-69.477c4.746-7.688,2.375-17.75-5.312-22.492 c-7.688-4.777-17.75-2.375-22.497,5.313l-31.066,50.273l-9.301-11.012C136.763,151.843,126.467,150.956,119.556,156.792z"></path>
                                <path d="M158.72,245.094c-13.554,0-24.535,10.978-24.535,24.517c0,13.543,10.98,24.52,24.535,24.52 c13.543,0,24.52-10.977,24.52-24.52C183.24,256.07,172.263,245.094,158.72,245.094z"></path>
                                <path d="M355.568,351.359h-111.71c-9.047,0-16.355,7.309-16.355,16.358c0,9.017,7.309,16.34,16.355,16.34h111.71 c9.047,0,16.354-7.323,16.354-16.34C371.924,358.667,364.615,351.359,355.568,351.359z"></path>
                                <path d="M158.72,343.199c-13.554,0-24.535,10.977-24.535,24.52c0,13.539,10.98,24.521,24.535,24.521 c13.543,0,24.52-10.979,24.52-24.521C183.24,354.176,172.263,343.199,158.72,343.199z"></path>
                                <path d="M173.463,75.613h138.73c3.401,0,6.613-1.521,8.746-4.176c2.137-2.629,2.961-6.105,2.229-9.43L311.686,8.859 C310.564,3.687,305.994,0,300.708,0H184.963c-5.281,0-9.852,3.688-10.977,8.859l-11.5,53.148 c-0.695,3.324,0.125,6.801,2.247,9.43C166.868,74.093,170.08,75.613,173.463,75.613z"></path>
                        </svg>
                    </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    /**
     * Exporta os produtos em formato de XLSX
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [
            'ID',
            'FAMILIA',
            'EAN',
            'SKU',
            'DESCRICAO',
            'TIPO',
            'LINHA',
            'GRUPO',
            'SUBGRUPO',
            'PRECO',
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
        }

        $products = Products::select(
            'id',
            'family',
            'ean',
            'sku',
            'description',
            'type',
            'line',
            'group',
            'sub_group',
            'price'
        )->get();

        $rowIndex = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $rowIndex, $product->id);
            $sheet->setCellValue('B' . $rowIndex, $product->family);
            $sheet->setCellValue('C' . $rowIndex, "'" . $product->ean);
            $sheet->setCellValue('D' . $rowIndex, $product->sku);
            $sheet->setCellValue('E' . $rowIndex, $product->description);
            $sheet->setCellValue('F' . $rowIndex, $product->type);
            $sheet->setCellValue('G' . $rowIndex, $product->line);
            $sheet->setCellValue('H' . $rowIndex, $product->group);
            $sheet->setCellValue('I' . $rowIndex, $product->sub_group);
            $sheet->setCellValue('J' . $rowIndex, $product->price);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'produtos.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Cria um novo produto
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'family' => 'required|string|max:255',
            'ean' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'line' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'sub_group' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'customization' => 'required|integer',
        ], [
            'family.required' => 'O CAMPO FAMILIA E OBRIGATORIO.',
            'family.string' => 'A FAMILIA DEVE SER UMA STRING.',
            'family.max' => 'A FAMILIA NAO PODE TER MAIS DE 255 CARACTERES.',
            'ean.string' => 'O EAN DEVE SER UMA STRING.',
            'ean.max' => 'O EAN NAO PODE TER MAIS DE 255 CARACTERES.',
            'sku.string' => 'O SKU DEVE SER UMA STRING.',
            'sku.max' => 'O SKU NAO PODE TER MAIS DE 255 CARACTERES.',
            'description.string' => 'A DESCRICAO DEVE SER UMA STRING.',
            'description.max' => 'A DESCRICAO NAO PODE TER MAIS DE 255 CARACTERES.',
            'type.string' => 'O TIPO DEVE SER UMA STRING.',
            'type.max' => 'O TIPO NAO PODE TER MAIS DE 255 CARACTERES.',
            'line.string' => 'A LINHA DEVE SER UMA STRING.',
            'line.max' => 'A LINHA NAO PODE TER MAIS DE 255 CARACTERES.',
            'group.string' => 'O GRUPO DEVE SER UMA STRING.',
            'group.max' => 'O GRUPO NAO PODE TER MAIS DE 255 CARACTERES.',
            'sub_group.string' => 'O SUBGRUPO DEVE SER UMA STRING.',
            'sub_group.max' => 'O SUBGRUPO NAO PODE TER MAIS DE 255 CARACTERES.',
            'price.numeric' => 'O PRECO DEVE SER UM NUMERO.',
            'price.min' => 'O PRECO DEVE SER ZERO OU MAIOR.',
            'customization.integer' => 'CUSTOMIZACAO DEVE SER UM NUMERO.',
            'customization.required' => 'TIPO DE CUSTOMIZACAO DEVE SER UM SELECIONADO.',
        ]);

        $validatedData['user_id'] = Auth::id();

        Products::create($validatedData);

        return Inertia::location(route('products.index'));
    }

    /**
     * Atualiza um produto existente
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|integer|exists:products,id',
            'family' => 'required|string|max:255',
            'ean' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'customization' => 'required|integer',
        ], [
            'id.required' => 'O ID DO PRODUTO É OBRIGATÓRIO.',
            'id.exists' => 'PRODUTO NÃO ENCONTRADO.',
            'family.required' => 'O CAMPO FAMILIA É OBRIGATÓRIO.',
            'family.string' => 'A FAMILIA DEVE SER UMA STRING.',
            'family.max' => 'A FAMILIA NÃO PODE TER MAIS DE 255 CARACTERES.',
            'ean.string' => 'O EAN DEVE SER UMA STRING.',
            'ean.max' => 'O EAN NÃO PODE TER MAIS DE 255 CARACTERES.',
            'sku.string' => 'O SKU DEVE SER UMA STRING.',
            'sku.max' => 'O SKU NÃO PODE TER MAIS DE 255 CARACTERES.',
            'description.string' => 'A DESCRIÇÃO DEVE SER UMA STRING.',
            'description.max' => 'A DESCRIÇÃO NÃO PODE TER MAIS DE 255 CARACTERES.',
            'customization.required' => 'TIPO DE CUSTOMIZACAO DEVE SER UM SELECIONADO.',
            'customization.integer' => 'CUSTOMIZACAO DEVE SER UM NUMERO.',
        ]);

        $product = Products::findOrFail($validatedData['id']);
        $product->update($validatedData);

        return redirect()->back()->with('message', $validatedData['sku'] . ' atualizado com sucesso!');
    }


    /**
     * Mostra os produtos do usuario logado
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $products = Products::where('sku', 'LIKE', '%' . $request->input('sku') . '%')
            ->get();

        return response()->json($products);
    }

    /**
     * Mostra os produtos do usuario logado
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function byUser(Request $request)
    {
        $userId = Auth::id();

        $products = Products::join('queue', 'queue.product', '=', 'products.sku')
            ->leftJoin('product_transfer', 'product_transfer.queue_id', '=', 'queue.id')
            ->where('queue.product', 'LIKE', '%' . $request->input('sku') . '%')
            ->where('queue.status', 'PENDENTE')
            ->where('queue.user_id', $userId)
            ->whereNull('product_transfer.queue_id')
            ->get(['queue.id', 'products.sku', 'products.family']);

        return response()->json($products);
    }

    /**
     * Mostra os produtos do usuario logado
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function byAdmin(Request $request)
    {
        $userId = $request->input('userId');

        $products = Products::join('queue', 'queue.product', '=', 'products.sku')
            ->leftJoin('product_transfer', 'product_transfer.queue_id', '=', 'queue.id')
            ->where('queue.product', 'LIKE', '%' . $request->input('sku') . '%')
            ->where('queue.status', 'PENDENTE')
            ->where('queue.user_id', $userId)
            ->whereNull('product_transfer.queue_id')
            ->get(['queue.id', 'products.sku', 'products.family']);

        return response()->json($products);
    }
}
