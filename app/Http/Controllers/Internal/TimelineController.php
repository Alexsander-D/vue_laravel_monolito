<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\Timeline;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimelineController extends Controller
{
    /**
     * Mostra a pagina Timeline
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Internal/Timeline');
    }

    /**
     * Mostra as acoes do protocolo especificado na query string.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            'protocolo' => ['required', 'exists:entry,unique_id'],
        ], [
            'protocolo.required' => 'PREENCHA O CAMPO PROTOCOLO.',
            'protocolo.exists' => 'PROTOCOLO NÃO ENCONTRADO.',
        ]);

        $actions = Timeline::where('protocol', $validated['protocolo'])
            ->get();

        return Inertia::render('Internal/Timeline', [
            'actions' => $actions,
            'protocolo' => $validated['protocolo'],
        ]);
    }
}

