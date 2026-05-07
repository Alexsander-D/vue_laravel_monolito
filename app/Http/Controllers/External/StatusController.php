<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Material;
use App\Models\External\Screening;
use App\Models\External\ScreeningTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class StatusController extends Controller
{
    
/**
     * Cancela uma triagem. Setaando seu status para 'cancelada' e registrando a ação na timeline.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function cancelScreening(Request $request)
    {
        $request->validate([
            'screening_id' => 'required|exists:screening,id',
            'observation' => 'nullable|string|max:255',
        ]);

        $screening = Screening::findOrFail($request->screening_id);

        $screening->status = 'cancelada';
        $screening->observation = $request->observation;
        $screening->save();

        ScreeningTimeline::create([
            'screening_id' => $screening->id,
            'description' => "Triagem ID: {$screening->id} foi cancelada.",
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        return redirect()->route('ViewScreening.index');
    }

    /**
     * Atualiza o status da triagem.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'screening_id' => 'required|exists:screening,id', 
                'status' => 'required|string|max:50',
            ]);

            $screening = Screening::with('screeningReports')->findOrFail($validated["screening_id"]);

            // Verificação de triagem sem produtos
            if (
                $screening->type_service === 'pre-agenda'
                && ($screening->screeningReports->isEmpty() || empty($screening->screeningReports))
            ) {
                throw ValidationException::withMessages([
                    'screening_id' => 'A triagem está sem produtos atrelados. Não é possível alterar o status.'
                ]);
            }

            // Verificação de material
            $material = Material::where('screening_id', $screening->id)->first();
            if (!$material || empty($material->status) || $material->status === 'Pendente') {
                throw ValidationException::withMessages([
                    'status' => 'Não é possível confirmar a triagem com status vazio ou pendente no material.'
                ]);
            }

            // Atualiza status
            $screening->status = $validated['status'];
            $screening->save();

            // Registra na timeline
            ScreeningTimeline::create([
                'screening_id' => $screening->id,
                'description' => "Status da triagem ID: {$screening->id} atualizado para '{$screening->status}'.",
                'responsible' => Auth::user()->name ?? 'Sistema',
                'route' => route('ViewScreening.index'),
            ]);

            //  Redirecionamentos especiais
            if ($validated['status'] === 'aguardando_produtos') {
                return redirect()->route('ProductEntry.index', ['screeningId' => $screening->id]);
            } elseif ($validated['status'] === 'aguardando_agendamento') {
                return redirect()->route('customers.scheduling', ['screeningId' => $screening->id]);
            }

            return redirect()->back()->with('success', 'Status atualizado com sucesso.');
        } catch (ValidationException $e) {
            // Erros de validação já tratados
            throw $e;
        } catch (\Exception $e) {
            // Qualquer erro inesperado
            return redirect()->back()->withErrors([
                'status' => 'Ocorreu um erro inesperado ao atualizar o status. Tente novamente.' + $e->getMessage()
            ]);
        }
    }

    /**
     * Atualiza o status da triagem sem verificar o material.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateStatusWithoutMaterialCheck(Request $request)
    {
        $request->validate([
            'screening_id' => 'required|exists:screening,id',
            'status' => 'required|array',
            'status.label' => 'required|string|max:255',
        ]);

        $screening = Screening::with('screeningReports')->findOrFail($request->screening_id);

        if ($screening->type_service === 'pre-agenda' && ($screening->screeningReports->isEmpty() || empty($screening->screeningReports))) {
            throw ValidationException::withMessages([
                'screening_id' => 'A triagem está sem produtos atrelados. Não é possível alterar o status.'
            ]);
        }

        $screening->status = $request->status['label'];
        $screening->save();

        ScreeningTimeline::create([
            'screening_id' => $screening->id,
            'description' => "Status da triagem ID: {$screening->id} atualizado para '{$screening->status}' (sem verificação de material).",
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        if ($request->status['value'] === 'aguardando_produtos') {
            return redirect()->route('ProductEntry.index', ['screeningId' => $screening->id]);
        } elseif ($request->status['value'] === 'aguardando_agendamento') {
            return redirect()->route('customers.scheduling', ['screeningId' => $screening->id]);
        }

        return redirect()->back()->with('success', 'Status atualizado com sucesso.');
    }

}
