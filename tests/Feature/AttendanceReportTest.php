<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceService;
use App\Models\Spatie\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $pixAttendance = Attendance::create([
            'user_id' => $user->id,
            'total' => 100,
            'payment_method' => 'Pix',
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AttendanceService::create([
            'attendance_id' => $cashAttendance->id,
            'service_name' => 'Barba',
            'price' => 80,
        ]);

        $response = $this->get(route('attendance.report', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'payment_method' => 'Pix',
        ]));

        $response->assertOk();
        $response->assertViewHas('records', function ($records) {
            return $records->count() === 1
                && $records->first()->payment_method === 'Pix';
        });
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
