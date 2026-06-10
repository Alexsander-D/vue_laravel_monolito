<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string'],
            'services.*.price' => ['required', 'numeric'],
        ], [
            'services.required' => 'SELECIONE AO MENOS UM SERVIÇO.',
        ])->validate();

        DB::transaction(function () use ($validated) {

            $total = collect($validated['services'])
                ->sum('price');

            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'total' => $total,
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
}