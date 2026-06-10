<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string'],
            'services.*.price' => ['required', 'numeric'],
            'payment_method' => ['required', 'string', 'in:Dinheiro,Cartão,Pix'],
        ], [
            'services.required' => 'SELECIONE AO MENOS UM SERVIÇO.',
            'payment_method.in' => 'FORMA DE PAGAMENTO INVÁLIDA.',
        ])->validate();

        DB::transaction(function () use ($validated) {

            $total = collect($validated['services'])
                ->sum('price');

            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'total' => $total,
                'payment_method' => $validated['payment_method'],
            ]);

            foreach ($validated['services'] as $service) {

                AttendanceService::create([
                    'attendance_id' => $attendance->id,
                    'service_name' => $service['name'],
                    'price' => $service['price'],
                ]);
            }
        });

        return back()->with(
            'success',
            'ATENDIMENTO REGISTRADO COM SUCESSO.'
        );
    }

    public function report()
    {
        $records = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->leftJoin('attendance_services', 'attendance_services.attendance_id', '=', 'attendances.id')
            ->select(
                'attendances.id as attendance_id',
                'attendances.user_id',
                'users.name as user_name',
                DB::raw("GROUP_CONCAT(attendance_services.service_name SEPARATOR ', ') as service_name"),
                DB::raw('COALESCE(SUM(attendance_services.price), 0) as price'),
                'attendances.payment_method',
                'attendances.created_at'
            )
            ->whereDate('attendances.created_at', Carbon::today())
            ->groupBy(
                'attendances.id',
                'attendances.user_id',
                'users.name',
                'attendances.payment_method',
                'attendances.created_at'
            )
            ->orderBy('attendances.created_at', 'desc')
            ->orderBy('users.name', 'asc')
            ->get();

        return Inertia::render('Attendance/Report', [
            'records' => $records,
        ]);
    }
}
