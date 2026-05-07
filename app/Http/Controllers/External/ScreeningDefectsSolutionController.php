<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\Registration\Components;
use App\Models\Registration\DefectsSolutions;
use App\Models\External\ScreeningDefectsSolution;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScreeningDefectsSolutionController extends Controller
{
    public function update(Request $request, $screeningReportId)
    {
        $request->validate([
            'product' => 'required|array',
            'product.label' => 'required|string',

            // ✅ agora pode vir vazio
            'failureSolution' => 'nullable|array',
            'failureSolution.*' => 'nullable|array',
            'failureSolution.*.selectComponent' => 'nullable|array',
            'failureSolution.*.selectComponent.value' => 'nullable|exists:components,id',
            'failureSolution.*.selectFailure' => 'nullable|array',
            'failureSolution.*.selectFailure.value' => 'nullable|exists:defects_solutions,id',

            'status' => ['required', 'not_in:pendente'],
        ], [
            'status.required' => 'O campo status é obrigatório.',
            'status.not_in' => 'O status precisa ser alterado. O valor padrão "pendente" não é permitido.',
        ]);

        $screeningReport = ScreeningReport::findOrFail($screeningReportId);

        $screeningReport->update([
            'imei1' => $request->imei1,
            'imei2' => $request->imei2,
            'serial_number' => $request->serial_number,
            'hardware_version' => $request->hardware_version,
            'qr_code' => $request->qr_code,
            'gemco' => $request->gemco,
            'seal' => $request->seal,
            'fm' => $request->fm,
            'UniqueID' => $request->UniqueID,
            'patrimony' => $request->patrimony,
            'observation' => $request->observation,
            'status' => $request->status,
        ]);

        // ✅ Sempre remove as anteriores
        ScreeningDefectsSolution::where('screening_report_id', $screeningReport->id)->delete();

        // ✅ Adiciona novas somente se houver
        if (!empty($request->failureSolution)) {
            foreach ($request->failureSolution as $item) {
                if (
                    isset($item['selectComponent']['value']) &&
                    isset($item['selectFailure']['value'])
                ) {
                    ScreeningDefectsSolution::create([
                        'screening_report_id' => $screeningReport->id,
                        'component_id' => $item['selectComponent']['value'],
                        'defects_solutions_id' => $item['selectFailure']['value'],
                        'product' => $request->product['label'],
                    ]);
                }
            }
        }

        // Timeline
        $defeitoSolucaoTexto = "Laudo do produto com serial {$request->serial_number} atualizado.";

        ScreeningTimeline::create([
            'screening_id' => $screeningReport->screening_id,
            'description' => $defeitoSolucaoTexto,
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        /**
         * ✅ NOVO REDIRECIONAMENTO COM REGRA DE PRIORIDADE
         */

        $currentProductId = $screeningReport->products_id;

        // 1️⃣ Primeiro busca pendente com mesmo produto
        $nextSameProduct = ScreeningReport::where('status', 'pendente')
            ->where('id', '!=', $screeningReport->id)
            ->where('screening_id', $screeningReport->screening_id)
            ->where('products_id', $currentProductId)
            ->orderBy('id')
            ->first();

        if ($nextSameProduct) {
            return \Inertia\Inertia::location(
                route('productivityReport.show', ['screeningReportId' => $nextSameProduct->id])
            );
        }

        // 2️⃣ Não existindo: busca próximos pendentes normais
        $nextScreeningReport = ScreeningReport::where('status', 'pendente')
            ->where('id', '!=', $screeningReport->id)
            ->where('screening_id', $screeningReport->screening_id)
            ->orderBy('id')
            ->first();

        if ($nextScreeningReport) {
            return \Inertia\Inertia::location(
                route('productivityReport.show', ['screeningReportId' => $nextScreeningReport->id])
            );
        }

        // 3️⃣ Não existindo nenhum → volta para a triagem principal
        return \Inertia\Inertia::location(
            route('productivityReport.view', ['screeningId' => $screeningReport->screening_id])
        );
    }
}
