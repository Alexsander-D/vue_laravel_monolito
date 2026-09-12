<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\StockProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StockController extends Controller
{
    public function index()
    {
        $stocks = StockProduct::with('movements')
            ->orderBy('product_name')
            ->get();

        $movements = StockMovement::with('stockProduct')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Internal/Stock/Index', [
            'stocks' => $stocks,
            'movements' => $movements,
        ]);
    }

    public function export(Request $request, string $table)
    {
        abort_unless(in_array($table, ['products', 'movements'], true), 404);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        /** @var \App\Models\Spatie\User $user */
        $user = Auth::user();
        $isAdmin = $user->getRoleByUser() === 'Admin';

        if ($table === 'products') {
            $headers = ['Produto', 'Quantidade'];

            if ($isAdmin) {
                $headers[] = 'Preço de custo';
            }

            $headers[] = 'Preço de venda';
            $rows = StockProduct::query()->orderBy('product_name')->get();
        } else {
            $headers = ['Produto', 'Tipo', 'Quantidade', 'Preço', 'Data'];
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $type = $request->input('type');

            $rows = StockMovement::with('stockProduct')
                ->when($startDate, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
                ->when(in_array($type, ['entrada', 'baixa', 'venda'], true), function ($query) use ($type) {
                    if ($type === 'venda') {
                        return $query->where('description', 'Venda de estoque');
                    }

                    return $query->where('type', $type)->where('description', '!=', 'Venda de estoque');
                })
                ->orderByDesc('created_at')
                ->get();
        }

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
        }

        foreach ($rows as $rowIndex => $row) {
            if ($table === 'products') {
                $values = [$row->product_name, $row->quantity];

                if ($isAdmin) {
                    $values[] = $row->cost_price;
                }

                $values[] = $row->price;
            } else {
                $movementType = $row->description === 'Venda de estoque' ? 'Venda' : ucfirst($row->type);
                $values = [
                    $row->stockProduct?->product_name ?? 'Produto removido',
                    $movementType,
                    $row->quantity,
                    $row->price,
                    $row->created_at?->format('d/m/Y H:i:s'),
                ];
            }

            foreach ($values as $columnIndex => $value) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex + 1) . ($rowIndex + 2);
                $sheet->setCellValue($cell, $value ?? '');
            }
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'estoque_' . $table . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
        ], [
            'product_name.required' => 'O nome do produto é obrigatório.',
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'cost_price.required' => 'O preço de custo é obrigatório.',
            'cost_price.numeric' => 'O preço de custo deve ser um número.',
            'cost_price.min' => 'O preço de custo deve ser zero ou maior.',
            'price.required' => 'O preço de venda é obrigatório.',
            'price.numeric' => 'O preço de venda deve ser um número.',
            'price.min' => 'O preço de venda deve ser zero ou maior.',
        ]);

        $stock = StockProduct::create([
            'product_name' => $validated['product_name'],
            'quantity' => $validated['quantity'],
            'cost_price' => $validated['cost_price'],
            'price' => $validated['price'],
            'user_id' => Auth::id(),
        ]);

        StockMovement::create([
            'stock_product_id' => $stock->id,
            'type' => 'entrada',
            'quantity' => $stock->quantity,
            'price' => $stock->price,
            'user_id' => Auth::id(),
            'description' => 'Entrada inicial de estoque',
        ]);

        return back()->with('success', 'Produto adicionado ao estoque com sucesso.');
    }

    public function update(Request $request, StockProduct $stock)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
        ], [
            'product_name.required' => 'O nome do produto é obrigatório.',
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser zero ou maior.',
            'cost_price.required' => 'O preço de custo é obrigatório.',
            'cost_price.numeric' => 'O preço de custo deve ser um número.',
            'cost_price.min' => 'O preço de custo deve ser zero ou maior.',
            'price.required' => 'O preço de venda é obrigatório.',
            'price.numeric' => 'O preço de venda deve ser um número.',
            'price.min' => 'O preço de venda deve ser zero ou maior.',
        ]);

        $quantityDiff = $validated['quantity'] - $stock->quantity;

        if ($validated['quantity'] === 0) {
            if ($stock->quantity > 0) {
                StockMovement::create([
                    'stock_product_id' => $stock->id,
                    'type' => 'baixa',
                    'quantity' => $stock->quantity,
                    'price' => $validated['price'],
                    'user_id' => Auth::id(),
                    'description' => 'Produto removido do estoque',
                ]);
            }

            $stock->delete();

            return back()->with('success', 'Produto removido do estoque com sucesso.');
        }

        $stock->update([
            'product_name' => $validated['product_name'],
            'quantity' => $validated['quantity'],
            'cost_price' => $validated['cost_price'],
            'price' => $validated['price'],
        ]);

        if ($quantityDiff !== 0) {
            StockMovement::create([
                'stock_product_id' => $stock->id,
                'type' => $quantityDiff > 0 ? 'entrada' : 'baixa',
                'quantity' => abs($quantityDiff),
                'price' => $validated['price'],
                'user_id' => Auth::id(),
                'description' => $quantityDiff > 0 ? 'Entrada de estoque' : 'Baixa de estoque',
            ]);
        }

        return back()->with('success', 'Estoque atualizado com sucesso.');
    }

    public function destroy(StockProduct $stock)
    {
        if ($stock->quantity > 0) {
            StockMovement::create([
                'stock_product_id' => $stock->id,
                'type' => 'baixa',
                'quantity' => $stock->quantity,
                'price' => $stock->price,
                'user_id' => Auth::id(),
                'description' => 'Produto excluído do estoque',
            ]);
        }

        $stock->delete();

        return back()->with('success', 'Produto excluído do estoque com sucesso.');
    }

    public function sell(Request $request, StockProduct $stock)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $stock->quantity,
        ], [
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'quantity.max' => 'A quantidade informada não pode ser maior que a disponível.',
        ]);

        $stock->decrement('quantity', $validated['quantity']);

        StockMovement::create([
            'stock_product_id' => $stock->id,
            'type' => 'baixa',
            'quantity' => $validated['quantity'],
            'price' => $stock->price,
            'user_id' => Auth::id(),
            'description' => 'Venda de estoque',
        ]);

        return back()->with('success', 'Venda registrada com sucesso.');
    }
}
