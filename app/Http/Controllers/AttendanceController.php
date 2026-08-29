<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceService;
use App\Models\Spatie\User;
use App\Notifications\DailyAttendanceReportNotification;
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
            'payment_method' => ['required', 'string', 'in:Dinheiro,Cartão,Pix,Robert'],
        ], [
            'services.required' => 'SELECIONE AO MENOS UM SERVIÇO.',
            'payment_method.in' => 'FORMA DE PAGAMENTO INVÁLIDA.',
        ])->validate();

        try {
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

            return response()->json([
                'message' => 'ATENDIMENTO REGISTRADO COM SUCESSO.',
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['email_error' => $e->getMessage()]);
        }
    }

    public function report(Request $request)
    {
        $startDate = $request->input('start_date', $request->input('startDate'));
        $endDate = $request->input('end_date', $request->input('endDate'));
        $paymentMethod = $request->input('payment_method', $request->input('paymentMethod'));

        $records = Attendance::query()
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
            ->when($paymentMethod, function ($query, $value) {
                $query->where('attendances.payment_method', $value);
            })
            ->dateRange($startDate, $endDate)
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
            'date' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'paymentMethod' => $paymentMethod,
            ],
        ]);
    }

    public function sendProductivityReport(Request $request)
    {
        $startDate = $request->input('startDate', $request->input('start_date'));
        $endDate = $request->input('endDate', $request->input('end_date'));
        $paymentMethod = $request->input('paymentMethod', $request->input('payment_method'));

        $attendances = Attendance::query()
            ->when($startDate, fn($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('created_at', '<=', $endDate))
            ->when($paymentMethod, fn($query) => $query->where('payment_method', $paymentMethod))
            ->with('services')
            ->get();

        $hoursWithAttendance = array_fill_keys(range(9, 21), false);
        foreach ($attendances as $attendance) {
            $hour = (int) $attendance->created_at->format('H');
            if ($hour >= 9 && $hour <= 21) {
                $hoursWithAttendance[$hour] = true;
            }
        }

        $idleHours = [];
        foreach (range(9, 21) as $hour) {
            if (!($hoursWithAttendance[$hour] ?? false)) {
                $idleHours[] = sprintf('%02d:00 - %02d:00', $hour, $hour + 1);
            }
        }

        $totalAttendances = $attendances->count();
        $totalRevenue = $attendances->sum('total');
        $occupiedHours = count(array_filter($hoursWithAttendance));
        $occupancyPercentage = $occupiedHours > 0 ? round(($occupiedHours / count($hoursWithAttendance)) * 100) : 0;
        $dateLabel = $startDate && $endDate
            ? sprintf('%s até %s', $startDate, $endDate)
            : now()->format('d/m/Y');

        $attendanceData = [
            'total_attendances' => $totalAttendances,
            'total_revenue' => $totalRevenue,
            'idle_hours' => $idleHours,
            'occupancy_percentage' => $occupancyPercentage,
            'date' => $dateLabel,
        ];

        $toArray = User::where('id', '!=', 1)->get();
        foreach ($toArray as $responsavel) {
            $responsavel->notify(new DailyAttendanceReportNotification($attendanceData));
        }

        return response()->json([
            'message' => 'Relatório de produtividade enviado por e-mail.',
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = Validator::make($request->all(), [
            'payment_method' => ['nullable', 'string', 'in:Dinheiro,Cartão,Pix,Robert'],
            'services' => ['nullable', 'array'],
            'services.*.name' => ['required_with:services', 'string', 'min:1'],
            'services.*.price' => ['required_with:services', 'numeric', 'min:0'],
        ])->validate();

        if (! empty($validated['payment_method'])) {
            $attendance->update([
                'payment_method' => $validated['payment_method'],
            ]);
        }

        if (array_key_exists('services', $validated)) {
            $services = collect($validated['services'])
                ->map(fn ($service) => [
                    'name' => trim((string) $service['name']),
                    'price' => (float) ($service['price'] ?? 0),
                ])
                ->filter(fn ($service) => $service['name'] !== '')
                ->values()
                ->all();

            $attendance->services()->delete();

            foreach ($services as $service) {
                $attendance->services()->create([
                    'service_name' => $service['name'],
                    'price' => $service['price'],
                ]);
            }

            $attendance->update([
                'total' => collect($services)->sum('price'),
            ]);
        }

        return redirect()->back();
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()->back();
    }
}
