<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\Queue;
use App\Models\Internal\Report;
use App\Models\Internal\Timeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QueueController extends Controller
{
    /**
     * Mostra a lista de filas do usuario logado.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();
        $queues = Queue::withQueueByEntriesWithTeamAndPendent($user)->get();
        $widget = Queue::withWidgets($user)->first();

        $widgets = [
            ['title' => 'PENDENTE', 'value' => $widget->PENDENTE ?? 0],
            ['title' => 'RECUPERADO', 'value' => $widget->RECUPERADO ?? 0],
            ['title' => 'DESCARTE', 'value' => $widget->DESCARTE ?? 0],
            ['title' => 'TOTAL TRATADO', 'value' => $widget->TOTAL ?? 0],
        ];

        return Inertia::render('Internal/Queue', [
            'queues' => $queues,
            'widgets' => $widgets,
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
        $defectsSolutions = Report::getReportInfoByQueueId($request["queueId"]);

        return Inertia::render('Internal/Report', [
            'reportInfo' => $reportInfo,
            'defectsSolutionsInfo' => $defectsSolutions,
        ]);
    }

    /**
     * Atualiza a informacao de uma fila.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'queueId' => 'required|exists:queue,id',

            'product.label' => 'required|string',
            'product.value' => 'required|integer|exists:products,id',

            'product_new.label' => 'nullable|string',
            'product_new.value' => 'nullable|integer|exists:products,id',

            'serial_number' => 'nullable|string',
            'status' => 'required|string',

            'product_lot' => [
                'required',
                'string',
                'regex:/^[A-Za-z]{3}/'
            ],

            'is_misuse' => 'nullable|in:Sim,Não',
            'imei1' => 'nullable|string',
            'imei2' => 'nullable|string',
            'observation' => 'nullable|string',

            'failureSolution' => 'required|array|min:1',
            'failureSolution.*.id' => 'nullable|integer',
            'failureSolution.*.selectComponent.value' => 'required|integer|exists:components,id',
            'failureSolution.*.selectFailure.value' => 'required|integer|exists:defects_solutions,id',
        ], [
            // queueId
            'queueId.required' => 'O ID DA FILA E OBRIGATORIO.',
            'queueId.exists' => 'A FILA INFORMADA NAO FOI ENCONTRADA.',

            // Produto
            'product.label.required' => 'SELECIONE UM PRODUTO.',
            'product.label.string' => 'O NOME DO PRODUTO DEVE SER UM TEXTO.',
            'product.value.required' => 'SELECIONE UM PRODUTO VALIDO.',
            'product.value.integer' => 'ID DO PRODUTO INVALIDO.',
            'product.value.exists' => 'O PRODUTO SELECIONADO NAO EXISTE.',

            // Produto novo
            'product_new.label.string' => 'O NOME DO NOVO PRODUTO DEVE SER UM TEXTO.',
            'product_new.value.integer' => 'ID DO NOVO PRODUTO INVALIDO.',
            'product_new.value.exists' => 'O NOVO PRODUTO SELECIONADO NAO EXISTE.',

            // Serial
            'serial_number.string' => 'O NUMERO DE SERIE DEVE SER UM TEXTO.',

            // Status
            'status.required' => 'O STATUS E OBRIGATORIO.',
            'status.string' => 'O STATUS DEVE SER UM TEXTO.',

            // Lote
            'product_lot.required' => 'O CAMPO LOTE E OBRIGATORIO.',
            'product_lot.string' => 'O LOTE DEVE SER UM TEXTO.',
            'product_lot.regex' => 'O CAMPO LOTE DEVE COMECAR COM TRES LETRAS.',

            // IMEIs
            'imei1.string' => 'O IMEI1 DEVE SER UM TEXTO.',
            'imei2.string' => 'O IMEI2 DEVE SER UM TEXTO.',

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

        $productName = $validated['product']['label'];
        $productNameNew = empty($validated['product_new']['label']) ? $productName : $validated['product_new']['label'];

        foreach ($validated['failureSolution'] as $index => $defectSolution) {
            $queueId = $validated['queueId'];
            $defectSolutionId = $defectSolution['selectFailure']['value'];
            $reportId = $defectSolution['id'] ?? null;

            $exists = Report::where('queue_id', $queueId)
                ->where('defect_solution_id', $defectSolutionId)
                ->when($reportId, fn($q) => $q->where('id', '!=', $reportId))
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'submitReportForm' => 'NAO E POSSIVEL CADASTRAR A MESMA FALHA DUAS VEZES PARA O MESMO PRODUTO.'
                ]);
            }

            if ($reportId) {
                Report::where('id', $reportId)->update([
                    'defect_solution_id' => $defectSolutionId,
                    'observation' => $validated['observation'] ?? null,
                ]);
            } else {
                Report::firstOrCreate([
                    'queue_id' => $queueId,
                    'defect_solution_id' => $defectSolutionId,
                ], [
                    'observation' => $validated['observation'] ?? null,
                ]);
            }
        }

        Queue::where('id', $validated['queueId'])->update([
            'product' => $productName,
            'product_new' => $productNameNew,
            'serial_number' => $validated['serial_number'],
            'status' => $validated['status'],
            'product_lot' => $validated['product_lot'],
            'imei1' => $validated['imei1'],
            'imei2' => $validated['imei2'],
            'is_misuse' => $validated['is_misuse'] ?? 'Não',
        ]);

        $protocol = $validated['queueId'];
        $description = "LAUDO REALIZADO: " . $validated['status'];

        Timeline::createHistory($protocol, $description);

        return redirect()->route('queue.index');
    }
}
