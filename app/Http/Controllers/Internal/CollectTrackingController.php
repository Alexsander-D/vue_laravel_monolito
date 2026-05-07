<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\TrackingProtocol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CollectTrackingController extends Controller
{
    /**
     * Mostra a tela de rastreamento para coleta
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $tracking = TrackingProtocol::whereDate('updated_at', now())
            ->whereIn('status', ['AGUARDANDO CONFIRMACAO', 'ENTREGUE'])
            ->get();

        return Inertia::render('Internal/Entrance', compact('tracking'));
    }

    /**
     * Filtra os rastreamentos por data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function show(Request $request)
    {
        $startDate = $request->input('startDate') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('endDate') ?? now()->endOfMonth()->format('Y-m-d');

        $tracking = TrackingProtocol::whereBetween('updated_at', [$startDate, $endDate])
            ->get();

        return Inertia::render('Internal/Entrances', [
            'tracking' => $tracking,
            'date' => compact('startDate', 'endDate')
        ]);
    }

    /**
     * Cria um novo rastreamento
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $request->validate([
            'trackingInput' => 'required|string',
        ], [
            'trackingInput.required' => 'CODIGO DE RASTREAMENTO OBRIGATORIO',
            'trackingInput.string' => 'CODIGO DE RASTREAMENTO INVALIDO',
        ]);

        $user = Auth::user();

        $tracking = TrackingProtocol::with('user:id,name')
            ->where('tracking', $request->trackingInput)
            ->first();

        if (!$tracking) {
            TrackingProtocol::create([
                'user_id' => $user->id,
                'tracking' => $request->trackingInput,
                'responsable' => $user->name . ' - SAC',
                'status' => 'ENTREGUE',
            ]);

            return back()->with('message', 'CODIGO DE RASTREAMENTO CRIADO COM SUCESSO!');
        }

        switch ($tracking->status) {
            case 'SOLICITADO':
                $tracking->update([
                    'received_by' => $user->id,
                    'status' => 'AGUARDANDO CONFIRMACAO',
                ]);

                $responsableName = $tracking->user->name ?? 'RESPONSAVEL NAO DEFINIDO';

                return back()->with('message', "ATENCAO! *{$request->trackingInput}* DEVE SER SEPARADO PARA {$responsableName}");

            case 'AGUARDANDO CHEGADA':
                $tracking->update([
                    'received_by' => $user->id,
                    'status' => 'ENTREGUE',
                ]);

                return back();

            case 'ENTREGUE':
                return back()->withErrors(['trackingInput' => 'ESSE CODIGO DE RASTREAMENTO JA FOI CADASTRADO!'])->withInput();

            default:
                return back()->withErrors(['trackingInput' => 'STATUS DESCONHECIDO PARA ESSE CODIGO.'])->withInput();
        }
    }
}

