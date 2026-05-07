<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Internal\Entry;
use App\Models\Internal\Queue;
use App\Models\Internal\Timeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class EntryController extends Controller
{
    /**
     * Exibe a tela de cria o de entradas.
     */
    public function index()
    {
        return Inertia::render('Internal/DataEntry', [
            'entries' => Entry::getEntriesOnQueues(),
        ]);
    }

    /**
     * Armazena uma nova entrada.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function create(Request $request)
    {
        $product = $request->input('product');
        $user = Auth::user();
        $isRMA = $user->currentTeam->name === 'RMA';

        $validatedData = Validator::make($request->all(), [
            'entryInput' => 'required|unique:entry,unique_id',
        ], [
            'entryInput.unique' => 'O VALOR FORNECIDO JA ESTA EM USO.',
        ])->validate();

        $entry = Entry::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'unique_id' => $validatedData['entryInput'],
        ]);

        if ($isRMA) {
            for ($i = 0; $i < $request->input('quantity'); $i++) {
                Queue::createQueueOnRMA($entry->id, $product["label"], "PENDENTE");
            }
        } else {
            Queue::createQueueOnSAC($entry->id, "PENDENTE");
        }

        Timeline::createHistory($validatedData['entryInput'], "ENTRADA NO " . $user->currentTeam->name);

        return back()->with('success', 'ENTRADA CRIADA COM SUCESSO.');
    }

    /**
     * Remove uma entrada.
     *
     * @param int $id
     */
    public function destroy($id)
    {
        $entry = Entry::findOrFail($id);

        $entry->queue()->delete();
        $entry->delete();

        return response()->json(['success' => 'ENTRADA DELETADA COM SUCESSO.']);
    }
}

