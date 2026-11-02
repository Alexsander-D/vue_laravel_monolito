<?php

namespace Tests\Feature;

use App\Models\GoaliTrip;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoaliTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_the_datetime_local_value_as_date_and_time(): void
    {
        $user = User::create([
            'name' => 'Operador Teste',
            'email' => 'motorista@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($user, 'Admin');
        $driver = User::create([
            'name' => 'Motorista Teste',
            'email' => 'driver@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->teams()->attach($team->id, ['role' => 'Motorista']);
        $outsider = User::create([
            'name' => 'Espectador Teste',
            'email' => 'spectator@example.com',
            'password' => Hash::make('password'),
        ]);
        $outsider->teams()->attach($team->id, ['role' => 'Espectador']);

        $this->actingAs($user)
            ->get(route('goali.form'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goali/Form')
                ->where('initialTripStatus', 'Em Andamento')
                ->where('drivers', [
                    ['id' => $driver->id, 'name' => $driver->name],
                    ['id' => $user->id, 'name' => $user->name],
                ]));

        $this->actingAs($user)
            ->from('/goali/form')
            ->post(route('goali.store'), [
                'travel_datetime' => '2026-10-31T23:45',
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => $driver->id,
                'origin' => 'Origem informada',
                'destination' => 'Destino informado',
                'passenger' => 'Passageiro Teste',
                'responsible' => 'Outro responsável',
                'amount' => '125.50',
            ])
            ->assertRedirect('/goali/form');

        $this->assertDatabaseHas('goali_trips', [
            'user_id' => $user->id,
            'travel_date' => '2026-10-31',
            'travel_time' => '23:45',
            'status' => 'ENTRADA',
            'trip_status' => 'Em Andamento',
            'driver_id' => $driver->id,
            'solicitation' => 'IMB',
            'origin' => 'Origem informada',
            'destination' => 'Destino informado',
            'passenger' => 'Passageiro Teste',
            'responsible' => 'Operador Teste',
            'amount' => '125.50',
        ]);

        $trip = GoaliTrip::where('passenger', 'Passageiro Teste')->firstOrFail();
        $this->put(route('goali.update', $trip), [
            'travel_date' => '2026-11-01',
            'travel_time' => '08:30',
            'status' => 'SAÍDA',
            'solicitation' => 'IMB',
            'driver_id' => $driver->id,
            'origin' => 'Origem atualizada',
            'destination' => 'Destino atualizado',
            'passenger' => 'Passageiro atualizado',
            'responsible' => 'Operador Teste',
            'amount' => '150.00',
        ])->assertRedirect();

        $this->assertDatabaseHas('goali_trips', [
            'id' => $trip->id,
            'travel_date' => '2026-11-01',
            'travel_time' => '08:30',
            'status' => 'SAÍDA',
            'trip_status' => 'Em Andamento',
            'driver_id' => $driver->id,
            'origin' => 'Origem atualizada',
            'passenger' => 'Passageiro atualizado',
            'amount' => '150.00',
        ]);

        $this->delete(route('goali.destroy', $trip))->assertRedirect();
        $this->assertDatabaseMissing('goali_trips', ['id' => $trip->id]);
    }

    public function test_it_filters_and_sorts_trips_by_date_and_time(): void
    {
        $user = User::create([
            'name' => 'Relatório Teste',
            'email' => 'report@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $createTrip = fn (string $time, string $status) => GoaliTrip::create([
            'user_id' => $user->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => $time,
            'status' => $status,
            'trip_status' => $status === 'ENTRADA' ? 'Finalizada' : 'Em Andamento',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Passageiro',
            'responsible' => $user->name,
            'amount' => '10.00',
        ]);

        $lateTrip = $createTrip('18:30', 'ENTRADA');
        $createTrip('12:00', 'SAÍDA');
        $earlyTrip = $createTrip('08:15', 'ENTRADA');

        $this->actingAs($user)
            ->get(route('goali.index', [
                'status' => 'ENTRADA',
                'tripStatus' => 'Finalizada',
                'sortOrder' => 'asc',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goali/Index')
                ->where('summary.count', 2)
                ->where('summary.receivable', 20)
                ->where('records.0.id', $earlyTrip->id)
                ->where('records.1.id', $lateTrip->id)
                ->where('filters.sortOrder', 'asc'));

        $this->get(route('goali.index', [
            'status' => 'ENTRADA',
            'tripStatus' => 'Finalizada',
            'sortOrder' => 'desc',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goali/Index')
                ->where('summary.count', 2)
                ->where('summary.receivable', 20)
                ->where('records.0.id', $lateTrip->id)
                ->where('records.1.id', $earlyTrip->id)
                ->where('filters.sortOrder', 'desc'));
    }

    public function test_non_admin_trip_starts_created_and_driver_can_only_finalize_running_trips(): void
    {
        $driver = User::create([
            'name' => 'Motorista Goali',
            'email' => 'motorista-goali@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($driver, 'Motorista');

        $this->actingAs($driver, 'sanctum')
            ->post(route('goali.store'), [
                'travel_datetime' => now()->format('Y-m-d\TH:i'),
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => $driver->id,
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => 'Passageiro',
                'responsible' => $driver->name,
                'amount' => '50.00',
            ])
            ->assertRedirect();

        $createdTrip = GoaliTrip::where('passenger', 'Passageiro')->firstOrFail();
        $this->assertSame('Criada', $createdTrip->trip_status);

        $this->actingAs($driver, 'sanctum')
            ->put(route('goali.update', $createdTrip), ['trip_status' => 'Finalizada'])
            ->assertForbidden();

        $runningTrip = GoaliTrip::create([
            'user_id' => $driver->id,
            'driver_id' => $driver->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Em Andamento',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida em andamento',
            'responsible' => $driver->name,
            'amount' => '75.00',
        ]);

        $this->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isAdmin', false)
                ->where('isDriver', true));

        $this->put(route('goali.update', $runningTrip), ['trip_status' => 'Finalizada'])
            ->assertRedirect();
        $this->assertSame('Finalizada', $runningTrip->fresh()->trip_status);
        $this->put(route('goali.update', $runningTrip), [])
            ->assertForbidden();
        $this->delete(route('goali.destroy', $runningTrip))
            ->assertForbidden();
    }

    public function test_admin_created_trips_start_in_progress_and_admin_can_only_advance_one_status(): void
    {
        $admin = User::create([
            'name' => 'Admin Goali',
            'email' => 'admin-goali@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($admin, 'Admin');

        $this->actingAs($admin)
            ->get(route('goali.form'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('initialTripStatus', 'Em Andamento'));

        $trip = GoaliTrip::create([
            'user_id' => $admin->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Criada',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida criada',
            'responsible' => $admin->name,
            'amount' => '75.00',
        ]);

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip, 'Em Andamento'))
            ->assertRedirect();
        $this->assertSame('Em Andamento', $trip->fresh()->trip_status);

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip, 'Finalizada'))
            ->assertRedirect();
        $this->assertSame('Finalizada', $trip->fresh()->trip_status);

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip, 'Finalizada'))
            ->assertForbidden();

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip))
            ->assertForbidden();
    }

    public function test_users_on_the_unassigned_team_see_the_access_notice_instead_of_dashboard(): void
    {
        $user = User::create([
            'name' => 'Usuário sem equipe',
            'email' => 'sem-equipe@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $team = new Team([
            'name' => 'EQUIPE NÃO ATRIBUÍDA',
            'personal_team' => false,
        ]);
        $team->user_id = $user->id;
        $team->save();
        $user->teams()->attach($team->id, ['role' => 'Espectador']);
        $user->forceFill(['current_team_id' => $team->id])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/UnassignedTeam'));
    }

    private function attachToGoaliTeam(User $user, string $role): Team
    {
        $team = new Team([
            'name' => 'GOALI TRANSPORTES',
            'personal_team' => false,
        ]);
        $team->user_id = $user->id;
        $team->save();
        $user->teams()->attach($team->id, ['role' => $role]);
        $user->forceFill(['current_team_id' => $team->id])->save();

        return $team;
    }

    private function tripUpdatePayload(GoaliTrip $trip, ?string $tripStatus = null): array
    {
        return array_filter([
            'travel_date' => $trip->travel_date->format('Y-m-d'),
            'travel_time' => substr($trip->travel_time, 0, 5),
            'status' => $trip->status,
            'trip_status' => $tripStatus,
            'driver_id' => $trip->driver_id ?? $trip->user_id,
            'solicitation' => $trip->solicitation,
            'origin' => $trip->origin,
            'destination' => $trip->destination,
            'passenger' => $trip->passenger,
            'responsible' => $trip->responsible,
            'amount' => $trip->amount,
        ], fn ($value) => $value !== null);
    }
}
