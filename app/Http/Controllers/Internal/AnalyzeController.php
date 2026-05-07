<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\Analysis;
use App\Models\Internal\Queue;
use App\Models\Internal\Timeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyzeController extends Controller
{
    /**
     * Mostra a lista de filas do usuario logado.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();
        $queues = Queue::withQueueByEntriesWithAnalyzes($user)->get();

        return Inertia::render('Internal/Analyze', [
            'queues' => $queues,
        ]);
    }

    /**
     * Mostra a informacao de uma fila especifica.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function show(Request $request)
    {
        $reportInfo = Queue::getQueueInfoByQueueId($request["queueId"]);
        $analyseInfo = Analysis::withQueueByEntriesWithAnalyzes()->get();

        return Inertia::render('Internal/AnalyzeReport', [
            'reportInfo' => $reportInfo,
            'defectsSolutionsInfo' => $analyseInfo,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'queueId' => 'required|exists:queue,id',
            'serial_number' => 'nullable|string',
            'observation' => 'nullable|string',
            'failureSolution' => 'required|array|min:1',
            'failureSolution.*.id' => 'nullable|integer',
            'failureSolution.*.selectComponent.value' => 'required|integer|exists:components,id',
            'failureSolution.*.selectFailure.value' => 'required|integer|exists:defects_solutions,id',
        ], [
            // queueId
            'queueId.required' => 'O ID DA FILA E OBRIGATORIO.',
            'queueId.exists' => 'A FILA INFORMADA NAO FOI ENCONTRADA.',

            // Serial
            'serial_number.string' => 'O NUMERO DE SERIE DEVE SER UM TEXTO.',

            // Status
            'status.required' => 'O STATUS E OBRIGATORIO.',
            'status.string' => 'O STATUS DEVE SER UM TEXTO.',

            // Observacao
            'observation.string' => 'A OBSERVACAO DEVE SER UM TEXTO.',

            // Falhas/Solucoes
            'failureSolution.required' => 'INFORME PELO MENOS UMA FALHA/SOLUCAO.',
            'failureSolution.array' => 'O CAMPO FALHA/SOLUCAO DEVE SER UMA LISTA.',
            'failureSolution.min' => 'INFORME PELO MENOS UMA FALHA/SOLUCAO.',

            'failureSolution.*.id.integer' => 'FALHA INVALIDA, CONTACTE O ADMINISTRADOR.',
            'failureSolution.*.id.nullable' => 'FALHA SELECIONADA NAO ENCONTRADA.',

            'failureSolution.*.selectComponent.value.required' => 'SELECIONE O COMPONENTE.',
            'failureSolution.*.selectComponent.value.integer' => 'ID DE COMPONENTE INVALIDO.',
            'failureSolution.*.selectComponent.value.exists' => 'O COMPONENTE SELECIONADO NAO EXISTE.',

            'failureSolution.*.selectFailure.value.required' => 'SELECIONE A FALHA/SOLUCAO.',
            'failureSolution.*.selectFailure.value.integer' => 'ID DE FALHA/SOLUCAO INVALIDO.',
            'failureSolution.*.selectFailure.value.exists' => 'A FALHA/SOLUCAO SELECIONADA E INVALIDA.',
        ]);

        $queueId = $validated['queueId'];
        $userId = Auth::id();
        try {
            foreach ($validated['failureSolution'] as $index => $defectSolution) {
                $reportId = $defectSolution['id'] ?? null;

                $defectSolutionId = $defectSolution['selectFailure']['value'] ?? null;
                if (!$defectSolutionId) {
                    return back()->withErrors(['submitReportForm' => "Falha inválida na linha $index."]);
                }

                $exists = Analysis::where('queue_id', $queueId)
                    ->where('defect_solution_id', $defectSolutionId)
                    ->when($reportId, fn($q) => $q->where('id', '!=', $reportId))
                    ->exists();

                if ($exists) {
                    return back()->withErrors([
                        'submitReportForm' => 'NAO E POSSIVEL CADASTRAR A MESMA FALHA DUAS VEZES PARA O MESMO PRODUTO.'
                    ]);
                }

                if ($reportId) {
                    Analysis::where('id', $reportId)->update([
                        'defect_solution_id' => $defectSolutionId,
                        'user_id' => $userId,
                        'observation' => $validated['observation'] ?? null,
                        'status' => $validated['status'],
                    ]);
                } else {
                    Analysis::firstOrCreate([
                        'queue_id' => $queueId,
                        'defect_solution_id' => $defectSolutionId,
                    ], [
                        'user_id' => $userId,
                        'observation' => $validated['observation'] ?? null,
                        'status' => $validated['status'],
                    ]);
                }
            }

            Queue::where('id', $queueId)->update([
                'serial_number' => $validated['serial_number'],
                'status' => $validated['status'] === 'DESCARTE' ? 'DESCARTE' : 'ANALISADO',
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['submitReportForm' => $e->getMessage()]);
        }

        $protocol = $queueId;
        $description = "ANALISE REALIZADA: " . $validated['status'];

        Timeline::createHistory($protocol, $description);

        return redirect()->route('analyzes.index');
    }
}
