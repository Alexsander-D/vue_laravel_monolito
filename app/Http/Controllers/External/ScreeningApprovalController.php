<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Material;
use App\Models\External\Screening;
use App\Models\External\ScreeningReport;
use App\Models\Registration\Products;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\External\ScreeningTimeline;
use Illuminate\Support\Facades\Auth;

class ScreeningApprovalController extends Controller
{

    public function approve(Request $request)
    {
        $data = $request->validate([
            'screening_id' => 'required|integer|exists:screening,id',
            'rm' => 'nullable|string|max:255',
            'recovered' => 'nullable|string',
            'return' => 'nullable|string',
            'ndoa' => 'nullable|string',
            'status' => 'required|string|max:255',
            'approval_date' => 'required|date',
            'skus' => 'required|array',
            'skus.*.sku' => 'required|string|max:255',
            'skus.*.price' => 'required|numeric|min:0',
            'skus.*.editable' => 'required|boolean',
        ]);

        $screening = Screening::find($data['screening_id']);

        if (!$screening) {
            return response()->json(['message' => 'Triagem não encontrada.'], 404);
        }

        $material = Material::where('screening_id', $screening->id)->first();

        if (
            !$material ||
            empty($material->status) ||
            !in_array($material->status, ['Entregue', 'Cancelado'])
        ) {
            throw ValidationException::withMessages([
                'status' => 'Não é possível aprovar o laudo. O material precisa estar com status "Entregue" ou "Cancelado".'
            ]);
        }


        $screening->update([
            'rm' => $data['rm'],
            'recovered_value' => $data['recovered'],
            'return_value' => $data['return'],
            'ndoa_value' => $data['ndoa'],
            'status' => $data['status'],
            'approval_date' => $data['approval_date']
        ]);

        $skuMap = Products::whereIn('sku', collect($data['skus'])->pluck('sku'))
            ->pluck('id', 'sku');

        foreach ($data['skus'] as $skuItem) {
            $productId = $skuMap[$skuItem['sku']] ?? null;

            if ($productId) {
                ScreeningReport::where('screening_id', $screening->id)
                    ->where('products_id', $productId)
                    ->update(['price' => $skuItem['price']]);
            }
        }

        ScreeningTimeline::create([
            'screening_id' => $screening->id,
            'description' => 'Laudo aprovado para a triagem.',
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        $skus = ScreeningReport::where('screening_id', $screening->id)
            ->join('products', 'products.id', '=', 'screening_report.products_id')
            ->select(
                'products.sku',
                'products.family',
                'screening_report.price'
            )
            ->get();

        return redirect()->route('customers.finalReport', ['screeningId' => $screening->id])
            ->with('success', 'Laudo aprovado com sucesso!');
    }
    public function approveExcel(Request $request)
    {
        $validatedData = $request->validate([
            'screening_id' => 'required|integer|exists:screening,id',
            'rows' => 'required|array',
        ]);

        $rows = $validatedData['rows'];

        // Verifica se há pelo menos uma linha de dados além do cabeçalho
        if (count($rows) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'A planilha deve conter pelo menos uma linha de dados além do cabeçalho.'
            ], 422);
        }

        $screening = Screening::find($validatedData['screening_id']);

        if (!$screening) {
            return response()->json(['message' => 'Triagem não encontrada.'], 404);
        }

        $material = Material::where('screening_id', $screening->id)->first();

        if (
            !$material ||
            empty($material->status) ||
            !in_array($material->status, ['Entregue', 'Cancelado'])
        ) {
            return response()->json([
                'message' => 'Não é possível aprovar o laudo. O material precisa estar com status "Entregue" ou "Cancelada".'
            ], 422);
        }


        // Ignora a primeira linha (cabeçalho)
        $slicedRows = array_slice($rows, 1);

        // Remove linhas vazias
        $slicedRows = array_filter($slicedRows, function ($row) {
            return isset($row[0], $row[1]) && trim($row[0]) !== '' && trim($row[1]) !== '';
        });

        $grouped = [];
        $invalidSkus = [];

        foreach ($slicedRows as $row) {
            $sku = trim($row[0] ?? '');
            $value = $row[1] ?? null;

            if (is_string($value)) {
                $value = str_replace(['.', ','], ['', '.'], $value);
            }

            $price = is_numeric($value) ? floatval($value) : null;

            if ($sku && $price !== null && $price >= 0) {
                $grouped[strtoupper($sku)] = $price;
            }
        }

        if (empty($grouped)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum SKU válido encontrado na planilha.'
            ], 422);
        }

        $skuMap = Products::whereIn('sku', array_keys($grouped))->pluck('id', 'sku');

        foreach ($grouped as $sku => $price) {
            $productId = $skuMap[$sku] ?? null;

            if (!$productId) {
                $invalidSkus[] = $sku;
                continue;
            }

            $existsInScreening = ScreeningReport::where('screening_id', $screening->id)
                ->where('products_id', $productId)
                ->exists();

            if (!$existsInScreening) {
                $invalidSkus[] = $sku;
                continue;
            }

            ScreeningReport::where('screening_id', $screening->id)
                ->where('products_id', $productId)
                ->update(['price' => $price]);
        }

        if (!empty($invalidSkus)) {
            return response()->json([
                'success' => false,
                'message' => 'Os seguintes SKUs não pertencem à triagem: ' . implode(', ', $invalidSkus),
                'invalidSkus' => $invalidSkus
            ], 422);
        }

        ScreeningTimeline::create([
            'screening_id' => $screening->id,
            'description' => 'Preços importados via Excel.',
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        $skus = ScreeningReport::where('screening_id', $screening->id)
            ->join('products', 'products.id', '=', 'screening_report.products_id')
            ->select('products.sku', 'products.family', 'screening_report.price')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Preços aplicados com sucesso para a triagem.',
            'entries' => $skus,
        ]);
    }


    public function reprove(Request $request)
    {
        $data = $request->validate([
            'screening_id' => 'required|integer|exists:screening,id',
            'reject_report' => 'nullable|string|max:1000',
        ]);

        $screening = Screening::find($data['screening_id']);

        if (!$screening) {
            return response()->json(['message' => 'Triagem não encontrada.'], 404);
        }

        $screening->update([
            'reject_report' => $data['reject_report'],
            'status' => 'laudo reprovado',
        ]);

        ScreeningTimeline::create([
            'screening_id' => $screening->id,
            'description' => 'Laudo reprovado para a triagem.',
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        return redirect()->route('customers.finalReport', ['screeningId' => $screening->id])
            ->with('success', 'Laudo reprovado com sucesso!');
    }

}
