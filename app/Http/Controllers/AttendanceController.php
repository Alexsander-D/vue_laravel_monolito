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
        $records = AttendanceService::join('attendances', 'attendance_services.attendance_id', '=', 'attendances.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->select(
                'attendance_services.id as attendance_service_id',
                'attendances.id as attendance_id',
                'users.id as user_id',
                'users.name as user_name',
                'attendance_services.service_name',
                'attendance_services.price',
                'attendances.created_at as created_at'
            )
            ->whereDate('attendances.created_at', Carbon::today())
            ->orderBy('attendances.created_at', 'desc')
            ->orderBy('users.name', 'asc')
            ->orderBy('attendance_services.id', 'desc')
            ->get();

        return Inertia::render('Attendance/Report', [
            'records' => $records,
        ]);
    }
}
