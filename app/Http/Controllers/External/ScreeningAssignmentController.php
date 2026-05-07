<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Screening;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningTimeline;
use App\Models\External\TechnicalScale;
use App\Models\Spatie\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScreeningAssignmentController extends Controller
{

    public function updateTechnician(Request $request)
    {
        $request->validate([
            'screening_id' => 'required|exists:screening,id',
            'technician' => 'required|integer|exists:users,id',
        ], [
            'screening_id.required' => 'O campo de triagem é obrigatório.',
            'screening_id.exists' => 'A triagem selecionada não foi encontrada.',
            'technician.required' => 'O campo técnico é obrigatório.',
            'technician.integer' => 'O técnico selecionado deve ser um ID numérico.',
            'technician.exists' => 'O técnico selecionado não foi encontrado no sistema.',
        ]);

        $exists = TechnicalScale::where('screening_id', $request->screening_id)
            ->where('user_id', $request->technician)
            ->exists();

        if ($exists) {
            return back()->withErrors(['technician' => 'O técnico já está associado a essa triagem.']);
        }

        $technician = User::find($request->technician);

        if (!$technician) {
            return back()->with('error', 'Técnico não encontrado.');
        }

        TechnicalScale::create([
            'screening_id' => $request->screening_id,
            'user_id' => $request->technician,
            'technical' => $technician->name,
        ]);

        ScreeningTimeline::create([
            'screening_id' => $request->screening_id,
            'description' => "Técnico {$technician->name} adicionado à triagem.",
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        return back()->with('success', 'Técnico adicionado à triagem com sucesso!');
    }

    public function removeTechnician($id)
    {
        $technicalScale = TechnicalScale::find($id);

        if (!$technicalScale) {
            return back()->with('error', 'Técnico não encontrado.');
        }

        $screeningId = $technicalScale->screening_id;

        // 🔎 Verifica quantos técnicos estão vinculados à triagem
        $totalTechnicians = TechnicalScale::where('screening_id', $screeningId)->count();

        // 🚫 Bloqueia exclusão se for o último técnico
        if ($totalTechnicians <= 1) {
            return back()->withErrors([
                'technician' => 'Não foi possível remover o técnico.  Deve haver pelo menos um técnico vinculado à triagem.'
            ]);
        }

        $technicalName = $technicalScale->technical;

        // ✅ Remove o técnico
        $technicalScale->delete();

        ScreeningTimeline::create([
            'screening_id' => $screeningId,
            'description' => "Técnico {$technicalName} removido da triagem.",
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        return back()->with('success', 'Técnico removido com sucesso.');
    }

}
