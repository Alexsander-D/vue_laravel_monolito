<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\StockProduct;
use App\Models\Spatie\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public function export()
    {
        $stocks = StockProduct::orderBy('product_name')->get();
        $user = User::find(Auth::id());
        $isAdmin = strtolower($user?->getRoleByUser() ?? '') === 'admin';
        $headers = ['Produto', 'Quantidade'];

        if ($isAdmin) {
            $headers[] = 'Preço de custo';
        }

        $headers[] = 'Preço de venda';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . '1', $header);
        }

        foreach ($stocks as $rowIndex => $stock) {
            $values = [$stock->product_name, $stock->quantity];

            if ($isAdmin) {
                $values[] = $stock->cost_price;
            }

            $values[] = $stock->price;

            foreach ($values as $columnIndex => $value) {
                $sheet->setCellValue(chr(65 + $columnIndex) . ($rowIndex + 2), $value);
            }
        }

        return $this->downloadSpreadsheet($spreadsheet, 'produtos-em-estoque');
    }

    public function exportMovements(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'type' => ['nullable', 'in:todos,entrada,baixa,venda'],
        ]);

        $query = StockMovement::with('stockProduct')->orderBy('created_at', 'desc');
        $type = $validated['type'] ?? 'todos';

        if (!empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }

        if (!empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        if ($type === 'venda') {
            $query->where('type', 'baixa')->where('description', 'Venda de estoque');
        } elseif ($type === 'baixa') {
            $query->where('type', 'baixa')->where(function ($movementQuery) {
                $movementQuery->whereNull('description')->orWhere('description', '!=', 'Venda de estoque');
            });
        } elseif ($type !== 'todos') {
            $query->where('type', $type);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Produto', 'Tipo', 'Quantidade', 'Preço', 'Data'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . '1', $header);
        }

        foreach ($query->get() as $rowIndex => $movement) {
            $movementType = $movement->description === 'Venda de estoque' ? 'Venda' : ucfirst($movement->type);
            $values = [
                $movement->stockProduct?->product_name ?? 'Produto removido',
                $movementType,
                $movement->quantity,
                $movement->price,
                $movement->created_at?->format('d/m/Y H:i'),
            ];

            foreach ($values as $columnIndex => $value) {
                $sheet->setCellValue(chr(65 + $columnIndex) . ($rowIndex + 2), $value);
            }
        }

        return $this->downloadSpreadsheet($spreadsheet, 'log-entradas-e-baixas');
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $name)
    {
        $filename = $name . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $name);
        (new Xlsx($spreadsheet))->save($tempFile);

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
