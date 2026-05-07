<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\Entry;
use App\Models\Internal\Queue;
use App\Models\Internal\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SetQueueController extends Controller
{
    /**
     * Mostra a pagina SetQueue
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $users = Auth::user()->currentTeam->users;

        return Inertia::render('Internal/SetQueue', [
            'allowedUsers' => $users,
        ]);
    }

    /**
     * Retorna todas as entradas na fila do usuario especificado
     *
     * @param  int  $userId
     * @return \Illuminate\Support\Collection
     */
    public function data($userId)
    {
        return Entry::getEntriesOnQueues($userId);
    }

    public function datatable(Request $request)
    {
        $info = $this->data($request->id);

        return DataTables::of($info)
            ->addColumn('button', function ($data) {
                return '<button data-id="' . $data['id'] . '" type="button" class="delete-btn inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">x</button>';
            })
            ->rawColumns(['button'])
            ->make(true);
    }

    /**
     * Cria uma nova entrada ou atribui uma entrada existente a um usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $isRMA = $user->currentTeam->name === 'RMA' ?? false;

        $messages = [
            'responsableSelect.required' => 'O CAMPO RESPONSÁVEL É OBRIGATÓRIO.',
            'responsableSelect.array'    => 'O CAMPO RESPONSÁVEL DEVE SER UMA ARRAY.',
        ];

        if ($isRMA) {
            // força product como string simples (não objeto)
            $request->merge([
                'product' => $request->input('product.label'),
            ]);

            $messages = array_merge($messages, [
                'product.required' => 'O CAMPO PRODUTO É OBRIGATÓRIO.',
                'product.exists' => 'PRODUTO NÃO FOI ENCONTRADO. CONTACTE O ADMINSTRADOR!',
                'quantity.required' => 'VOCÊ DEVE PREENCHER O CAMPO QUANTIDADE.',
                'quantity.integer' => 'O CAMPO QUANTIDADE DEVE SER UM NÚMERO INTEIRO.',
                'quantity.min' => 'O CAMPO QUANTIDADE DEVE SER PELO MENOS 1.',
            ]);

            $rules = [
                'responsableSelect' => ['required', 'array'],
                'product' => 'required|exists:products,sku',
                'quantity' => 'required|integer|min:1',
            ];
        } else {
            $messages = array_merge($messages, [
                'uniqueId.required' => 'VOCÊ DEVE PREENCHER O PROTOCOLO.',
                'uniqueId.exists' => 'O PROTOCOLO NÃO FOI ENCONTRADO.',
            ]);

            $rules = [
                'responsableSelect' => ['required', 'array'],
                'uniqueId' => 'required|exists:entry,unique_id',
            ];
        }

        $validatedData = $request->validate($rules, $messages);


        if ($isRMA) {
            for ($i = 0; $i < $validatedData['quantity']; $i++) {
                $validatedData['uniqueId'] = now()->timestamp . $i;

                $entry = Entry::create([
                    'user_id' => $user->id,
                    'team_id' => $user->currentTeam->id,
                    'unique_id' => $validatedData['uniqueId'],
                ]);

                Queue::createQueueOnRMA($entry->id, $validatedData['product'], "PENDENTE");

                Queue::where('entry_id', $entry->id)
                    ->where('status', 'PENDENTE')
                    ->whereNull('user_id')
                    ->update(['user_id' => $validatedData['responsableSelect']['id']]);

                Timeline::createHistory($entry->unique_id, "ATRIBUÍDO AO COLABORADOR: " . $validatedData['responsableSelect']['name']);
            }
        } else {
            $entry = Entry::where('unique_id', $validatedData['uniqueId'])
                ->whereHas('queue', function ($query) {
                    $query->where('status', 'PENDENTE')
                        ->whereNull('user_id');
                })
                ->first();
            if (!$entry) {
                return back()->withErrors(['uniqueId' => 'ID ÚNICO NÃO ENCONTRADO'])->withInput();
            }

            Queue::where('entry_id', $entry->id)->update(['user_id' => $validatedData['responsableSelect']['id']]);

            $protocol = $validatedData['uniqueId'];
            Timeline::createHistory($protocol, "ATRIBUÍDO AO COLABORADOR: " . $validatedData['responsableSelect']['name']);
        }

        return back()->with('message', 'ENTRADA ATRIBUÍDA COM SUCESSO.');
    }

    /**
     * Deleta uma entrada e todas as suas filas.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id)
    {
        try {
            $entry = Entry::with('queue')->findOrFail($id);

            // Se existir fila, deleta
            if ($entry->queue) {
                $entry->queue()->delete();
            }

            // Agora deleta o próprio entry
            $entry->delete();

            return back()->with('success', 'ENTRADA DELETADA COM SUCESSO.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'ERRO AO DELETAR A ENTRADA.',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
