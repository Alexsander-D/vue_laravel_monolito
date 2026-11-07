<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceService;
use App\Models\Spatie\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_attendance_report_by_payment_method(): void
    {
        $user = User::create([
            'name' => 'Teste User',
            'email' => 'teste@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user);

        $pixAttendance = Attendance::create([
            'user_id' => $user->id,
            'total' => 100,
            'payment_method' => 'Pix',
            'barbers' => ['Kaique'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AttendanceService::create([
            'attendance_id' => $pixAttendance->id,
            'service_name' => 'Corte',
            'price' => 100,
        ]);

        $cashAttendance = Attendance::create([
            'user_id' => $user->id,
            'total' => 80,
            'payment_method' => 'Dinheiro',
            'barbers' => ['Ryan'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AttendanceService::create([
            'attendance_id' => $cashAttendance->id,
            'service_name' => 'Barba',
            'price' => 80,
        ]);

        $cardAttendance = Attendance::create([
            'user_id' => $user->id,
            'total' => 90,
            'payment_method' => 'Cartão',
            'barbers' => ['Kaique'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AttendanceService::create([
            'attendance_id' => $cardAttendance->id,
            'service_name' => 'Corte Social',
            'price' => 90,
        ]);

        $previousAttendance = Attendance::create([
            'user_id' => $user->id,
            'total' => 200,
            'payment_method' => 'Pix',
            'barbers' => ['Kaique'],
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        AttendanceService::create([
            'attendance_id' => $previousAttendance->id,
            'service_name' => 'Corte',
            'price' => 200,
        ]);
        DB::table('attendances')
            ->where('id', $previousAttendance->id)
            ->update(['created_at' => now()->subDay()]);
        $this->assertSame(now()->subDay()->toDateString(), $previousAttendance->fresh()->created_at->toDateString());

        $response = $this->get(route('attendance.report', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'payment_method' => 'Pix',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/Report')
            ->has('records', 1)
            ->where('records.0.payment_method', 'Pix')
            ->where('barberOptions', ['Kaique', 'Ryan']));

        $cardResponse = $this->get(route('attendance.report', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'paymentMethod' => 'Cartão',
        ]));

        $cardResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/Report')
            ->has('records', 1)
            ->where('records.0.payment_method', 'Cartão'));

        $combinedResponse = $this->get(route('attendance.report', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'paymentMethod' => 'pix_cartao',
        ]));

        $combinedResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/Report')
            ->has('records', 2)
            ->where('records', fn ($records) => collect($records)
                ->pluck('payment_method')
                ->sort()
                ->values()
                ->all() === ['Cartão', 'Pix']));

        $barberResponse = $this->get(route('attendance.report', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'barber' => 'Kaique',
            'paymentMethod' => 'Cartão',
        ]));

        $barberResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/Report')
            ->has('records', 1)
            ->where('records.0.payment_method', 'Cartão')
            ->where('records.0.barbers', ['Kaique'])
            ->where('date.barber', 'Kaique'));
    }

    public function test_it_updates_attendance_service_and_payment_method(): void
    {
        $user = User::create([
            'name' => 'Teste User',
            'email' => 'update@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'total' => 35,
            'payment_method' => 'Dinheiro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = AttendanceService::create([
            'attendance_id' => $attendance->id,
            'service_name' => 'Corte Social',
            'price' => 35,
        ]);

        $response = $this->put(route('attendance.update', ['attendance' => $attendance->id]), [
            'payment_method' => 'Pix',
            'service_name' => 'Barba',
            'service_price' => 20,
        ]);

        $response->assertOk();

        $attendance->refresh();
        $service->refresh();

        $this->assertSame('Pix', $attendance->payment_method);
        $this->assertSame(20, (float) $attendance->total);
        $this->assertSame('Barba', $attendance->services()->first()->service_name);
        $this->assertSame('20.00', (string) $attendance->services()->first()->price);
    }
}
