<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Screening;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class TechLogController extends Controller
{
    public function index()
    {
        $logInfo = Screening::select('id', 'status', 'service_start', 'company_name', 'state', 'city')
            ->whereIn('status', ['agendada', 'confirmada', 'cancelada', 'finalizada', 'laudo aprovado'])
            ->get();

        return Inertia::render('ExternalService/CustomerService/TechLogs', [
            'logInfo' => $logInfo
        ]);
    }

    public function datatable()
    {
        $userId = Auth::id();
        $userName = Auth::user()->name;

        $query = DB::table('customers')
            ->join('screening', 'customers.id', '=', 'screening.customers_id')
            ->join('technical_scales', 'screening.id', '=', 'technical_scales.screening_id')
            ->whereIn('screening.status', ['Confirmada', 'Agendada', 'cancelada', 'Finalizada', 'Laudo aprovado'])
            ->select(
                'screening.id',
                'customers.company_name',
                'screening.city',
                'screening.state',
                'screening.service_start',
                'screening.status'
            );

        if ($userName !== 'Admin User') {
            $query->where('technical_scales.user_id', $userId);
        }

        $data = $query->get();

        return DataTables::of($data)
            ->addColumn('button', function ($row) {
                $status = strtolower($row->status);

                $route = match ($status) {
                    'confirmada' => route('productivityReport.view', ['screeningId' => $row->id]),
                    'agendada', 'finalizada', 'cancelada', 'laudo aprovado' => route('customers.finalReport', ['screeningId' => $row->id]),
                    default => null,
                };

                if (!$route) {
                    return '<button type="button" class="cursor-not-allowed flex justify-center items-center gap-2 size-[38px] text-sm rounded-lg bg-gray-300 text-white opacity-60" disabled>✕</button>';
                }

                return '<a href="' . $route . '">
                <button type="button" data-id="' . $row->id . '" class="flex justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35m1.7-5.9a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                    </svg>
                </button>
            </a>';
            })
            ->editColumn('service_start', fn($row) => \Carbon\Carbon::parse($row->service_start)->format('d/m/y'))
            ->editColumn('status', fn($row) => strtoupper($row->status))
            ->rawColumns(['button'])
            ->make(true);
    }
}
