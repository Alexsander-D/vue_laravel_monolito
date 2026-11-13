<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceService;
use App\Models\AttendanceServicePrice;
use App\Models\Spatie\Team;
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
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'total' => 35,
            'payment_method' => 'Dinheiro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AttendanceService::create([
            'attendance_id' => $attendance->id,
            'service_name' => 'Corte Social',
            'price' => 35,
        ]);

        $response = $this->put(route('attendance.update', ['attendance' => $attendance->id]), [
            'payment_method' => 'Pix',
            'services' => [
                ['name' => 'Barba', 'price' => 20],
            ],
        ]);

        $response->assertRedirect();

        $attendance->refresh();

        $this->assertSame('Pix', $attendance->payment_method);
        $this->assertSame(25.0, (float) $attendance->total);
        $this->assertSame('Barba', $attendance->services()->first()->service_name);
        $this->assertSame(25.0, (float) $attendance->services()->first()->price);
    }

    public function test_admin_can_change_service_prices_used_by_attendance_forms(): void
    {
        $admin = $this->createUserWithRole('Admin', 'price-admin@example.com');
        $service = AttendanceServicePrice::query()->where('name', 'Barba')->firstOrFail();
        $services = AttendanceServicePrice::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (AttendanceServicePrice $item) => [
                'id' => $item->id,
                'price' => $item->id === $service->id ? '28.50' : $item->price,
            ])
            ->all();

        $this->actingAs($admin)
            ->get(route('attendance.service-prices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/ServicePrices')
                ->has('services', 10));

        $this->put(route('attendance.service-prices.update'), ['services' => $services])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_service_prices', [
            'id' => $service->id,
            'price' => '28.50',
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('isAdmin', true)
                ->where('services', fn ($items) => collect($items)->firstWhere('name', 'Barba')['price'] === 28.5));

        $this->get(route('attendance.report'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('serviceCatalog', fn ($items) => collect($items)->firstWhere('name', 'Barba')['price'] === 28.5));

        $this->postJson(route('attendance.store'), [
            'services' => [['name' => 'Barba', 'price' => 1]],
            'payment_method' => 'Pix',
            'barbers' => ['Kaique'],
        ])->assertOk();

        $this->assertDatabaseHas('attendance_services', [
            'service_name' => 'Barba',
            'price' => '28.50',
        ]);
    }

    public function test_only_admins_can_manage_service_prices(): void
    {
        $barber = $this->createUserWithRole('Barbeiro', 'price-barber@example.com');

        $this->actingAs($barber)
            ->get(route('attendance.service-prices.index'))
            ->assertForbidden();

        $this->put(route('attendance.service-prices.update'), ['services' => []])
            ->assertForbidden();
    }

    private function createUserWithRole(string $role, string $email): User
    {
        $user = User::create([
            'name' => $role,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        if (! Team::query()->whereKey(1)->exists()) {
            $placeholderTeam = new Team([
                'name' => 'Equipe sem atribuição',
                'personal_team' => false,
            ]);
            $placeholderTeam->user_id = $user->id;
            $placeholderTeam->save();
        }

        $team = new Team([
            'name' => 'Barbearia Teste',
            'personal_team' => false,
        ]);
        $team->user_id = $user->id;
        $team->save();

        $user->teams()->attach($team->id, ['role' => $role]);
        $user->forceFill(['current_team_id' => $team->id])->save();

        return $user;
    }
}
