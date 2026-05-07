<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Screening;
use App\Models\External\ScreeningReport;
use App\Models\External\ScreeningDefectsSolution;
use App\Models\External\TechnicalScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ViewProductivityController extends Controller
{
    public function index($screeningId)
    {
        /** @var \App\Models\Spatie\User $user */
        $user = Auth::user();

        // Papel do usuário no time atual (pegando pelo pivot da team_user)
        $userRole = $user?->teams()
            ->where('teams.id', $user->current_team_id)
            ->first()?->pivot?->role;

        // Admin sempre pode (case-insensitive)
        $isAdmin = strtolower($userRole ?? '') === 'admin';

        if (!$isAdmin) {
            $isLinked = TechnicalScale::where('screening_id', $screeningId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$isLinked) {
                abort(403, 'Acesso negado.');
            }
        }

        $reports = ScreeningReport::with(['screening:id,status', 'products:id,sku'])
            ->where('screening_id', $screeningId)
            ->get()
            ->map(function ($report) {
                $defectsSolutions = ScreeningDefectsSolution::with(['component:id,component', 'defectSolution:id,defect,solution'])
                    ->where('screening_report_id', $report->id)
                    ->get();

                return [
                    'id' => $report->id,
                    'status' => $report->status,
                    'product' => $report->products->sku ?? '',
                    'component' => $defectsSolutions->first()->component->component ?? '',
                    'defect' => $defectsSolutions->first()->defectSolution->defect ?? '',
                    'solution' => $defectsSolutions->first()->defectSolution->solution ?? '',
                    'imei1' => $report->imei1 ?? '',
                    'imei2' => $report->imei2 ?? '',
                    'serial_number' => $report->serial_number ?? '',
                    'observation' => $report->observation ?? '',
                    'guarantee' => $report->guarantee ?? '',
                ];
            });

        $counts = ScreeningReport::selectRaw("
        SUM(CASE WHEN status = 'RECUPERADO' THEN 1 ELSE 0 END) AS RECUPERADO,
        SUM(CASE WHEN status = 'DEVOLUÇÃO' THEN 1 ELSE 0 END) AS DEVOLUCAO,
        SUM(CASE WHEN status = 'FORA DE GARANTIA' THEN 1 ELSE 0 END) AS FORA_GARANTIA,
        SUM(CASE WHEN status = 'MAU USO' THEN 1 ELSE 0 END) AS MAU_USO
    ")
            ->where('screening_id', $screeningId)
            ->first();

        $formattedCounts = [
            ['title' => 'RECUPERADO', 'value' => $counts->RECUPERADO ?? 0],
            ['title' => 'DEVOLUÇÃO', 'value' => $counts->DEVOLUCAO ?? 0],
            ['title' => 'FORA DE GARANTIA', 'value' => $counts->FORA_GARANTIA ?? 0],
            ['title' => 'MAU USO', 'value' => $counts->MAU_USO ?? 0],
        ];

        $screening = Screening::select('id', 'company_name', 'city', 'state', 'type_service', 'status')
            ->where('id', $screeningId)
            ->first();

        return Inertia::render('ExternalService/CustomerService/Productivity', [
            'productivityData' => (object) [
                'screeningId' => (int) $screeningId,
                'screeningStatus' => $reports->isNotEmpty() ? $reports->first()['status'] : 'N/A',
                'screeningInfo' => $screening,
                'reports' => $reports->toArray(),
                'widgets' => $formattedCounts,
            ],
        ]);
    }

    public function updateScreeningStatus(Request $request)
    {
        $request->validate([
            'screening_id' => ['required'],
            'status' => ['required'],
        ], [
            'screening_id.required' => 'O ID da triagem é obrigatório.',
            'status.required' => 'O status da triagem é obrigatório.',
        ]);

        $screening = Screening::find($request->screening_id);
        if (!$screening) {
            return response()->json(['error' => 'Triagem não encontrada.'], 404);
        }

        $screening->status = $request->status;
        $screening->save();

        return redirect()->route('customers.finalReport', ['screeningId' => $screening->id]);
    }
}
