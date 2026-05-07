<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\TrackingProtocol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SeparatedTrackingController extends Controller
{
    /**
     * Renderiza a pagina TrackingProtocol
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Internal/TrackingProtocol');
    }

    public function create(Request $request)
    {
        $request->validate([
            'radioSelection' => 'required|string',
            'trackingInput' => 'required|string|unique:tracking_protocol,tracking',
            'responsableInput' => 'required|string|max:255',
        ], [
            'radioSelection.required' => 'A CATEGORIA E OBRIGATORIA.',
            'radioSelection.string' => 'A CATEGORIA DEVE SER UM TEXTO VALIDO.',
            'trackingInput.required' => 'O CAMPO DE RASTREAMENTO E OBRIGATORIO.',
            'trackingInput.string' => 'O RASTREAMENTO DEVE SER UM TEXTO VALIDO.',
            'trackingInput.unique' => 'ESTE CODIGO DE RASTREAMENTO JA ESTA EM USO.',
            'responsableInput.required' => 'O RESPONSVEL E OBRIGATORIO.',
            'responsableInput.string' => 'O NOME DO RESPONSVEL DEVE SER UM TEXTO VALIDO.',
            'responsableInput.max' => 'O NOME DO RESPONSVEL NAO PODE TER MAIS DE 255 CARACTERES.',
        ]);

        $mail = $request->radioSelection === 'AGUARDANDO CHEGADA';

        TrackingProtocol::create([
            'user_id' => Auth::id(),
            'tracking' => $request->trackingInput,
            'responsable' => $request->responsableInput,
            'mail' => $mail,
            'status' => $request->radioSelection,
        ]);

        return back()->with('message', 'CODIGO DE RASTREIO SEPARADO COM SUCESSO!');
    }

    /**
     * Retorna os dados do protocolo de rastreamento
     *
     * @return \Illuminate\Support\Collection
     */
    public function data()
    {
        return TrackingProtocol::whereIn('status', ['AGUARDANDO CHEGADA', 'SOLICITADO', 'AGUARDANDO CONFIRMACAO'])->get();
    }

    public function datatable()
    {
        return DataTables::of($this->data())
            ->addColumn('update', function ($data) {
                $disabled = $data['status'] === 'AGUARDANDO CONFIRMACAO' ? '' : 'disabled';

                return '<button type="button" data-id="' . $data['id'] . '" class="update-btn flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:bg-green-700 disabled:opacity-50 disabled:pointer-events-none" ' . $disabled . '>
                        &#10003;
                    </button>';
            })
            ->addColumn('delete', function ($data) {
                $disabled = $data['status'] === 'AGUARDANDO CHEGADA' || $data['status'] === 'SOLICITADO' ? '' : 'disabled';

                return '<button type="button" data-id="' . $data['id'] . '" class="delete-btn flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:bg-red-700 disabled:opacity-50 disabled:pointer-events-none" ' . $disabled . '>
                        &times;
                    </button>';
            })
            ->rawColumns(['update', 'delete'])
            ->make(true);
    }

    /**
     * Exclui um registro de protocolo de rastreamento
     *
     * @param int $id
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete($id)
    {
        TrackingProtocol::findOrFail($id)->delete();
    }

    public function update($id)
    {
        $tracking = TrackingProtocol::findOrFail($id);
        $tracking->status = 'ENTREGUE';
        $tracking->save();
    }

    /**
     * Importa codigos de rastreamento do excel
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function excel(Request $request)
    {
        $request->validate([
            'radioSelection' => 'required|string',
            'rows' => 'required|array',
        ], [
            'radioSelection.required' => 'A CATEGORIA E OBRIGATORIA.',
            'radioSelection.string' => 'A CATEGORIA DEVE SER UM TEXTO VALIDO.',
            'rows.required' => 'O CAMPO DE RASTREAMENTO E OBRIGATORIO.',
            'rows.array' => 'O RASTREAMENTO DEVE SER UM ARRAY VALIDO.',
        ]);

        $erros = [];

        $mail = $request->radioSelection === 'AGUARDANDO CHEGADA';

        foreach (array_slice($request->rows, 1) as $index => $row) {
            if (!isset($row[0]) || !isset($row[1])) {
                $erros[] = "Linha " . ($index + 2) . " inválida.";
                continue;
            }

            $tracking = trim($row[0]);

            if (TrackingProtocol::where('tracking', $tracking)->exists()) {
                $erros[] = $tracking . ";";
                continue;
            }

            TrackingProtocol::firstOrCreate([
                'tracking' => $tracking,
            ], [
                'user_id' => Auth::id(),
                'responsable' => Str::upper(trim($row[1])),
                'mail' => $mail,
                'status' => $request->radioSelection,
            ]);
        }

        if (count($erros)) {
            return back()->withErrors(['import_errors' => $erros]);
        }

        return back()->with('message', 'CODIGOS DE RASTREIO IMPORTADOS COM SUCESSO!');
    }
}

