<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\External\Screening;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentTeam = $user->currentTeam;

        // --- ROLE VIA PIVOT (A FORMA 100% GARANTIDA) ---
        $userRole = null;

        if ($currentTeam) {
            $pivot = $currentTeam->users()->where('user_id', $user->id)->first();
            $userRole = $pivot ? ($pivot->pivot->role ?? null) : null;
        }

        $adminRoles = ['Admin', 'Auxiliar Administrativo'];

        // Query base
        $query = Screening::with(['customers', 'technicians'])
            ->select('id', 'customers_id', 'type_service', 'status', 'service_start', 'completion_date');

        // Regras de visualização
        if (!in_array($userRole, $adminRoles)) {
            $query->whereHas('technicians', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Mapeamento dos eventos
        $events = $query->get()->map(function ($screening) {
            return [
                'id' => $screening->id,
                'company_name' => $screening->customers->company_name ?? 'N/A',
                'type_service' => $screening->type_service,
                'status' => $screening->status,
                'technicians' => $screening->technicians->pluck('name')->toArray(),
                'start' => optional($screening->service_start)->format('Y-m-d\TH:i:s'),
                'end' => optional($screening->completion_date)->format('Y-m-d\TH:i:s'),
            ];
        });

        // Técnicos disponíveis apenas para Admin / Aux Adm
        $technicians = in_array($userRole, $adminRoles)
            ? $currentTeam->giveUsersByTeam()->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name
            ])
            : [];

        return Inertia::render('ExternalService/CustomerService/Calendar', [
            'calendarData' => [
                'events' => $events,
                'technicians' => $technicians,
                'user_role' => $userRole,
            ],
        ]);
    }

    public function events(Request $request)
    {
        $user = Auth::user();
        $currentTeam = $user->currentTeam;

        // ROLE pelo pivot
        $userRole = null;
        if ($currentTeam) {
            $pivot = $currentTeam->users()->where('user_id', $user->id)->first();
            $userRole = $pivot ? ($pivot->pivot->role ?? null) : null;
        }

        $adminRoles = ['Admin', 'Auxiliar Administrativo'];
        $tecnicoId = $request->query('tecnico');

        $query = Screening::with(['customers', 'technicians'])
            ->select('id', 'customers_id', 'type_service', 'status', 'service_start', 'completion_date');

        if (!in_array($userRole, $adminRoles)) {
            $query->whereHas('technicians', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($tecnicoId) {
            $query->whereHas('technicians', function ($q) use ($tecnicoId) {
                $q->where('user_id', $tecnicoId);
            });
        }

        $events = $query->get()->map(function ($screening) {
            return [
                'id' => $screening->id,
                'company_name' => $screening->customers->company_name ?? 'N/A',
                'type_service' => $screening->type_service,
                'start' => optional($screening->service_start)->format('Y-m-d\TH:i:s'),
                'end' => optional($screening->completion_date)->format('Y-m-d\TH:i:s'),
                'status' => $screening->status,
                'technicians' => $screening->technicians->pluck('name')->toArray(),
            ];
        });

        return response()->json($events);
    }
}
