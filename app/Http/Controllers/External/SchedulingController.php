<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Customers;
use App\Models\External\Screening;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningTimeline;
use App\Models\External\TechnicalScale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SchedulingController extends Controller
{
    public function index($screeningId)
    {
        $screening = Screening::find($screeningId);

        $schedulingInfo = ScreeningReport::getProductsByScreening($screeningId);


        $customersInfo = Customers::whereHas('screenings', function ($query) use ($screeningId) {
            $query->where('id', $screeningId);
        })->first();

        $technicians =  Auth::user()->currentTeam->giveUsersByTeam();;
        $technicalScales = TechnicalScale::where('screening_id', $screeningId)->distinct()->get();

        $schedulingData = [
            'schedulingInfo' => $schedulingInfo,
            'technicians' => $technicians,
            'screeningId' => (int) $screeningId,
            'customersInfo' => $customersInfo,
            'technicalScales' => $technicalScales,
            'screeningStatus' => $screening ? $screening->status : 'N/A',
            'screeningTypeService' => $screening ? $screening->type_service : null,
        ];

        return Inertia::render('ExternalService/CustomerService/Scheduling', [
            'schedulingData' => $schedulingData,
        ]);
    }

    public function show(Request $request)
    {
        $userArray = $request->input('user_id');
        $userId = $userArray['id'] ?? null;
        $technical = $userArray['name'] ?? null;

        $request->merge([
            'user_id' => $userId,
            'technical' => $technical,
        ]);

        $validatedData = $request->validate([
            'screening_id' => 'required|exists:screening,id',
            'user_id' => 'required|exists:users,id|unique:technical_scales,user_id,NULL,id,screening_id,' . $request->input('screening_id'),
            'technical' => 'required',
        ], [
            'screening_id.required' => 'O campo de identificação da triagem é obrigatório.',
            'screening_id.exists' => 'A triagem informada não existe.',
            'user_id.required' => 'O campo de identificação do técnico é obrigatório.',
            'user_id.exists' => 'O técnico informado não existe.',
            'user_id.unique' => 'O técnico já foi adicionado a essa triagem.',
            'technical.required' => 'A seleção do técnico é obrigatória.',
        ]);


        TechnicalScale::create([
            'screening_id' => $validatedData['screening_id'],
            'user_id' => $validatedData['user_id'],
            'technical' => $validatedData['technical'],
        ]);

        ScreeningTimeline::create([
            'screening_id' => $validatedData['screening_id'],
            'description' => "Técnico {$validatedData['technical']} adicionado à triagem.",
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:technical_scales,id',
        ], [
            'id.required' => 'O campo de identificação é obrigatório.',
            'id.exists' => 'O registro informado não existe.',
        ]);

        $technicalScale = TechnicalScale::findOrFail($validated['id']);
        $screeningId = $technicalScale->screening_id;
        $technicalName = $technicalScale->technical;
        $technicalScale->delete();

        ScreeningTimeline::create([
            'screening_id' => $screeningId,
            'description' => "Técnico {$technicalName} removido da triagem.",
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        $technicalScales = TechnicalScale::where('screening_id', $screeningId)->get();

        return response()->json([
            'message' => 'Registro excluído com sucesso!',
            'technicalScales' => $technicalScales
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'screening_id' => 'required',
            'service_start' => 'required|date',
            'completion_date' => 'required|date|after_or_equal:service_start',
            'observation' => 'nullable|max:200',
            'air_ticket' => 'required',
            'scheduling_date' => 'required|date',
        ], [
            'screening_id.required' => 'O ID da triagem é obrigatório.',
            'service_start.required' => 'A data de início do atendimento é obrigatória.',
            'service_start.date' => 'A data de início deve ser uma data válida.',
            'completion_date.required' => 'A data de finalização do atendimento é obrigatória.',
            'completion_date.date' => 'A data de finalização deve ser uma data válida.',
            'completion_date.after_or_equal' => 'A data de finalização deve ser igual ou posterior à data de início.',
            'observation.max' => 'A observação não pode ter mais que 200 caracteres.',
            'air_ticket.required' => 'Informe se houve emissão de passagem aérea.',
            'scheduling_date.required' => 'A data de agendamento é obrigatória.',
            'scheduling_date.date' => 'A data de agendamento deve ser uma data válida.',
        ]);

        $screening = Screening::find($validated['screening_id']);

        if (!$screening) {
            return back()->withErrors(['message' => 'Triagem não encontrada.']);
        }

        $hasTechnicians = TechnicalScale::where('screening_id', $validated['screening_id'])->exists();

        if (!$hasTechnicians) {
            return back()->withErrors([
                'message' => 'Não é possível finalizar a triagem sem pelo menos um técnico escalado. Selecione um técnico antes de continuar.'
            ]);
        }

        $screening->update([
            'service_start' => $validated['service_start'],
            'completion_date' => $validated['completion_date'],
            'observation' => $validated['observation'] ?? null,
            'air_ticket' => $validated['air_ticket'],
            'status' => 'agendada',
            'scheduling_date' => $validated['scheduling_date'],
        ]);

        $inicio = Carbon::parse($validated['service_start'])->format('d/m/Y');
        $fim = Carbon::parse($validated['completion_date'])->format('d/m/Y');

        ScreeningTimeline::create([
            'screening_id' => $validated['screening_id'],
            'description' => "Triagem ID: {$validated['screening_id']} foi agendada. Período: {$inicio} até {$fim}.",
            'responsible' => Auth::user()->name,
            'route' => route('ViewScreening.index'),
        ]);

        return Inertia::location(route('ViewScreening.index'));
    }
}
