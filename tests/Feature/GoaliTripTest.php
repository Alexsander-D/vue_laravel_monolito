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
        $driver->forceFill(['current_team_id' => $team->id])->save();
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
                ->where('currentUserId', $user->id)
                ->where('drivers', [
                    ['id' => $driver->id, 'name' => $driver->name],
                    ['id' => $user->id, 'name' => $user->name],
                ]));

        $this->actingAs($user)
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
            ->assertRedirect(route('goali.index'));

        $this->assertDatabaseHas('goali_trips', [
            'user_id' => $user->id,
            'travel_date' => '2026-10-31',
            'travel_time' => '23:45',
            'status' => 'ENTRADA',
            'trip_status' => 'Criada',
            'driver_id' => $driver->id,
            'solicitation' => 'IMB',
            'origin' => 'Origem informada',
            'destination' => 'Destino informado',
            'passenger' => 'Passageiro Teste',
            'responsible' => 'Operador Teste',
            'amount' => '125.50',
        ]);

        $this->actingAs($user)
            ->post(route('goali.store'), [
                'travel_datetime' => '2026-11-02T09:00',
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => $user->id,
                'origin' => 'Origem própria',
                'destination' => 'Destino próprio',
                'passenger' => 'Corrida do administrador',
                'responsible' => $user->name,
                'amount' => '50.00',
            ])
            ->assertRedirect(route('goali.index'));
        $this->assertDatabaseHas('goali_trips', [
            'passenger' => 'Corrida do administrador',
            'trip_status' => 'Em Andamento',
            'driver_id' => $user->id,
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
            'trip_status' => 'Criada',
            'driver_id' => $driver->id,
            'origin' => 'Origem atualizada',
            'passenger' => 'Passageiro atualizado',
            'amount' => '150.00',
        ]);

        $this->delete(route('goali.destroy', $trip))->assertRedirect();
        $this->assertDatabaseMissing('goali_trips', ['id' => $trip->id]);
    }

    public function test_it_creates_one_trip_per_day_across_month_boundaries(): void
    {
        $user = User::create([
            'name' => 'Admin Goali',
            'email' => 'range-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($user, 'Admin');

        $this->actingAs($user)
            ->post(route('goali.store'), [
                'travel_start_date' => '2026-10-30',
                'travel_end_date' => '2026-11-02',
                'travel_time' => '08:45',
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => $user->id,
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => 'Passageiro do período',
                'responsible' => $user->name,
                'amount' => '125.50',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('goali_trips', 4);
        foreach (['2026-10-30', '2026-10-31', '2026-11-01', '2026-11-02'] as $date) {
            $this->assertDatabaseHas('goali_trips', [
                'user_id' => $user->id,
                'travel_date' => $date,
                'travel_time' => '08:45',
                'status' => 'ENTRADA',
                'trip_status' => 'Em Andamento',
                'solicitation' => 'IMB',
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => 'Passageiro do período',
                'amount' => '125.50',
            ]);
        }

        $this->actingAs($user)
            ->post(route('goali.store'), [
                'travel_start_date' => '2026-04-30',
                'travel_end_date' => '2026-05-02',
                'travel_time' => '09:15',
                'status' => 'SAÍDA',
                'solicitation' => 'IMB',
                'driver_id' => $user->id,
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => 'Passageiro do mês de 30 dias',
                'responsible' => $user->name,
                'amount' => '80.00',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('goali_trips', 7);
        foreach (['2026-04-30', '2026-05-01', '2026-05-02'] as $date) {
            $this->assertDatabaseHas('goali_trips', [
                'user_id' => $user->id,
                'travel_date' => $date,
                'travel_time' => '09:15',
                'status' => 'SAÍDA',
                'passenger' => 'Passageiro do mês de 30 dias',
            ]);
        }
    }

    public function test_it_rejects_a_date_range_that_ends_before_it_starts(): void
    {
        $user = User::create([
            'name' => 'Admin Goali',
            'email' => 'invalid-range-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($user, 'Admin');

        $this->actingAs($user)
            ->from('/goali/form')
            ->post(route('goali.store'), [
                'travel_start_date' => '2026-11-02',
                'travel_end_date' => '2026-10-30',
                'travel_time' => '08:45',
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => $user->id,
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => 'Período inválido',
                'responsible' => $user->name,
                'amount' => '125.50',
            ])
            ->assertRedirect('/goali/form')
            ->assertSessionHasErrors('travel_end_date');

        $this->assertDatabaseCount('goali_trips', 0);
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
            'driver_id' => $user->id,
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
                ->where('summary.amount', 20)
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
                ->where('summary.amount', 20)
                ->where('summary.receivable', 20)
                ->where('records.0.id', $lateTrip->id)
                ->where('records.1.id', $earlyTrip->id)
                ->where('filters.sortOrder', 'desc'));
    }

    public function test_trip_value_totals_exclude_unassigned_trips(): void
    {
        $admin = User::create([
            'name' => 'Admin Resumo',
            'email' => 'admin-summary@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($admin, 'Admin');

        $assignedTrip = GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => $admin->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Finalizada',
            'solicitation' => 'IMB',
            'origin' => 'Origem atribuída',
            'destination' => 'Destino',
            'passenger' => 'Corrida atribuída',
            'responsible' => $admin->name,
            'amount' => '75.00',
        ]);

        GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => null,
            'travel_date' => now()->toDateString(),
            'travel_time' => '13:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Criada',
            'solicitation' => 'IMB',
            'origin' => 'Origem sem motorista',
            'destination' => 'Destino',
            'passenger' => 'Corrida sem motorista',
            'responsible' => $admin->name,
            'amount' => '50.00',
        ]);

        $this->actingAs($admin)
            ->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.count', 2)
                ->where('summary.amount', 75)
                ->where('summary.receivable', 75));
    }

    public function test_it_defaults_to_today_and_prioritizes_trips_by_status(): void
    {
        $admin = User::create([
            'name' => 'Admin Prioridade',
            'email' => 'admin-priority@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($admin, 'Admin');

        $createTrip = fn (string $status, string $time, string $solicitation, string $type) => GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => $admin->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => $time,
            'status' => $type,
            'trip_status' => $status,
            'solicitation' => $solicitation,
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => $status,
            'responsible' => $admin->name,
            'amount' => '10.00',
        ]);

        $finishedTrip = $createTrip('Finalizada', '08:00', 'LOGVALE', 'SAÍDA');
        $awaitingTrip = $createTrip('Aguardando Analise', '09:00', 'FRANGO DOURADO', 'ENTRADA');
        $inProgressTrip = $createTrip('Em Andamento', '10:00', 'OSESP', 'SAÍDA');
        $createdTrip = $createTrip('Criada', '11:00', 'IMB', 'ENTRADA');

        $this->actingAs($admin)
            ->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.startDate', now()->toDateString())
                ->where('filters.endDate', now()->toDateString())
                ->where('records', fn ($records) => collect($records)->pluck('id')->all() === [
                    $createdTrip->id,
                    $inProgressTrip->id,
                    $awaitingTrip->id,
                    $finishedTrip->id,
                ]));

        $this->get(route('goali.index', [
            'status' => ['ENTRADA', 'SAÍDA'],
            'tripStatus' => ['Criada', 'Em Andamento', 'Aguardando Analise'],
            'solicitation' => ['IMB', 'OSESP', 'FRANGO DOURADO'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', ['ENTRADA', 'SAÍDA'])
                ->where('filters.tripStatus', ['Criada', 'Em Andamento', 'Aguardando Analise'])
                ->where('filters.solicitation', ['IMB', 'OSESP', 'FRANGO DOURADO'])
                ->where('records', fn ($records) => collect($records)->pluck('id')->all() === [
                    $createdTrip->id,
                    $inProgressTrip->id,
                    $awaitingTrip->id,
                ]));
    }

    public function test_non_admin_trip_starts_created_and_driver_sends_running_trips_for_analysis(): void
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
        $this->assertSame('Em Andamento', $createdTrip->trip_status);

        $this->actingAs($driver, 'sanctum')
            ->put(route('goali.update', $createdTrip), ['trip_status' => 'Finalizada'])
            ->assertSessionHasErrors('trip_status');
        $this->put(route('goali.update', $createdTrip), [
            'trip_status' => 'Aguardando Analise',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Aguardando Analise', $createdTrip->fresh()->trip_status);

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
        $this->assertSame($team->id, $driver->fresh()->currentTeam->id);
        $this->assertSame('Motorista', $driver->fresh()->getRoleByUser());

        $this->get(route('goali.form'))
            ->assertInertia(fn (Assert $page) => $page->where('initialTripStatus', 'Em Andamento'));

        $this->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isAdmin', false)
                ->where('isDriver', true));

        $this->put(route('goali.update', $runningTrip), [
            'trip_status' => 'Aguardando Analise',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('Aguardando Analise', $runningTrip->fresh()->trip_status);

        $createdTrip = GoaliTrip::create([
            'user_id' => $driver->id,
            'driver_id' => null,
            'travel_date' => now()->toDateString(),
            'travel_time' => '13:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Criada',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida ainda criada',
            'responsible' => $driver->name,
            'amount' => '40.00',
        ]);
        $this->put(route('goali.update', $createdTrip), [
            'trip_status' => 'Aguardando Analise',
        ])->assertForbidden();
        $this->assertSame('Criada', $createdTrip->fresh()->trip_status);
        $this->put(route('goali.update', $createdTrip), [
            'trip_status' => 'Em Andamento',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Em Andamento', $createdTrip->fresh()->trip_status);
        $this->assertSame($driver->id, $createdTrip->fresh()->driver_id);
        $this->put(route('goali.update', $createdTrip), [
            'trip_status' => 'Aguardando Analise',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Aguardando Analise', $createdTrip->fresh()->trip_status);

        $this->put(route('goali.update', $runningTrip), [
            'trip_status' => 'Finalizada',
        ])->assertSessionHasErrors('trip_status');
        $this->delete(route('goali.destroy', $runningTrip))
            ->assertForbidden();

        $driver->teams()->updateExistingPivot($team->id, ['role' => 'Admin']);

        $this
            ->get(route('goali.index'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.receivable', 0));

        $this
            ->put(route('goali.update', $runningTrip), $this->tripUpdatePayload($runningTrip, 'Finalizada'))
            ->assertRedirect();
        $this->assertSame('Finalizada', $runningTrip->fresh()->trip_status);

        $this
            ->get(route('goali.index'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.receivable', 75));
    }

    public function test_admin_can_finalize_an_in_progress_trip(): void
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
            'driver_id' => $admin->id,
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

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip))
            ->assertForbidden();
    }

    public function test_admin_can_cancel_an_analysis_trip_back_to_the_unassigned_queue(): void
    {
        $admin = User::create([
            'name' => 'Admin Cancelamento',
            'email' => 'admin-cancel@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($admin, 'Admin');
        $driver = User::create([
            'name' => 'Motorista Cancelamento',
            'email' => 'driver-cancel@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->teams()->attach($team->id, ['role' => 'Motorista']);

        $trip = GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => $driver->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Aguardando Analise',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida cancelada',
            'responsible' => $admin->name,
            'amount' => '75.00',
        ]);

        $this->actingAs($admin)
            ->put(route('goali.update', $trip), ['cancel_trip' => true])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Criada', $trip->fresh()->trip_status);
        $this->assertNull($trip->fresh()->driver_id);
        $this->assertSame('Admin', $trip->fresh()->cancelled_by_role);
        $this->assertSame($admin->name, $trip->fresh()->cancelled_by_name);
        $this->assertNotNull($trip->fresh()->cancelled_at);
    }

    public function test_admin_cannot_cancel_an_unassigned_created_trip(): void
    {
        $admin = User::create([
            'name' => 'Admin Corrida Criada',
            'email' => 'admin-created-cancel@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($admin, 'Admin');

        $trip = GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => null,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Criada',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida sem motorista',
            'responsible' => $admin->name,
            'amount' => '75.00',
        ]);

        $this->actingAs($admin)
            ->put(route('goali.update', $trip), ['cancel_trip' => true])
            ->assertForbidden();

        $this->assertSame('Criada', $trip->fresh()->trip_status);
        $this->assertNull($trip->fresh()->driver_id);
        $this->assertNull($trip->fresh()->cancelled_at);
    }

    public function test_driver_can_cancel_a_trip_and_is_identified_as_the_canceller(): void
    {
        $driver = User::create([
            'name' => 'Motorista Cancelador',
            'email' => 'driver-canceller@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($driver, 'Motorista');

        $trip = GoaliTrip::create([
            'user_id' => $driver->id,
            'driver_id' => $driver->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Em Andamento',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida cancelada pelo motorista',
            'responsible' => $driver->name,
            'amount' => '75.00',
        ]);

        $this->actingAs($driver)
            ->put(route('goali.update', $trip), ['cancel_trip' => true])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Criada', $trip->fresh()->trip_status);
        $this->assertNull($trip->fresh()->driver_id);
        $this->assertSame('Motorista', $trip->fresh()->cancelled_by_role);
        $this->assertSame($driver->name, $trip->fresh()->cancelled_by_name);
    }

    public function test_admin_can_create_an_unassigned_trip_for_a_driver_to_claim(): void
    {
        $admin = User::create([
            'name' => 'Admin Goali',
            'email' => 'unassigned-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($admin, 'Admin');

        $this->actingAs($admin)
            ->post(route('goali.store'), [
                'travel_start_date' => now()->toDateString(),
                'travel_end_date' => now()->toDateString(),
                'travel_time' => '12:00',
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => null,
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => 'Corrida sem motorista',
                'responsible' => $admin->name,
                'amount' => '100.00',
            ])
            ->assertRedirect();

        $trip = GoaliTrip::where('passenger', 'Corrida sem motorista')->firstOrFail();
        $this->assertNull($trip->driver_id);
        $this->assertSame('Criada', $trip->trip_status);

        $admin->teams()->updateExistingPivot($team->id, ['role' => 'Motorista']);

        $this->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('currentDriverId', $admin->id)
                ->where('records.0.id', $trip->id));

        $this->put(route('goali.update', $trip), [
            'trip_status' => 'Em Andamento',
            'driver_id' => $admin->id,
        ])->assertRedirect();

        $this->assertSame($admin->id, $trip->fresh()->driver_id);
        $this->assertSame('Em Andamento', $trip->fresh()->trip_status);

        $this->put(route('goali.update', $trip), [
            'trip_status' => 'Aguardando Analise',
            'driver_id' => $admin->id,
        ])->assertRedirect();
        $this->assertSame('Aguardando Analise', $trip->fresh()->trip_status);

    }

    public function test_only_an_admin_can_create_a_trip_without_a_driver(): void
    {
        $driver = User::create([
            'name' => 'Motorista Goali',
            'email' => 'required-driver@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($driver, 'Motorista');

        $this->actingAs($driver)
            ->from('/goali/form')
            ->post(route('goali.store'), [
                'travel_start_date' => now()->toDateString(),
                'travel_end_date' => now()->toDateString(),
                'travel_time' => '12:00',
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => null,
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => 'Corrida inválida',
                'responsible' => $driver->name,
                'amount' => '100.00',
            ])
            ->assertRedirect('/goali/form')
            ->assertSessionHasErrors('driver_id');

        $this->assertDatabaseMissing('goali_trips', ['passenger' => 'Corrida inválida']);
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
        $team->id = 1;
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

    public function test_dashboard_uses_the_current_team_id_when_user_belongs_to_multiple_teams(): void
    {
        $user = User::create([
            'name' => 'Usuário com equipe',
            'email' => 'com-equipe@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $unassignedTeam = new Team([
            'name' => 'EQUIPE NÃO ATRIBUÍDA',
            'personal_team' => false,
        ]);
        $unassignedTeam->user_id = $user->id;
        $unassignedTeam->save();
        $user->teams()->attach($unassignedTeam->id, ['role' => 'Espectador']);

        $assignedTeam = new Team([
            'name' => 'EQUIPE OPERACIONAL',
            'personal_team' => false,
        ]);
        $assignedTeam->user_id = $user->id;
        $assignedTeam->save();
        $user->teams()->attach($assignedTeam->id, ['role' => 'Espectador']);
        $user->forceFill(['current_team_id' => $assignedTeam->id])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
    }

    public function test_dashboard_redirects_to_goali_records_when_goali_is_the_current_team(): void
    {
        $user = User::create([
            'name' => 'Usuário Goali',
            'email' => 'goali-dashboard@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $otherTeam = new Team([
            'name' => 'EQUIPE OPERACIONAL',
            'personal_team' => false,
        ]);
        $otherTeam->user_id = $user->id;
        $otherTeam->save();
        $user->teams()->attach($otherTeam->id, ['role' => 'Espectador']);

        $goaliTeam = new Team([
            'name' => 'GOALI TRANSPORTES',
            'personal_team' => false,
        ]);
        $goaliTeam->user_id = $user->id;
        $goaliTeam->save();
        $user->teams()->attach($goaliTeam->id, ['role' => 'Admin']);
        $user->forceFill(['current_team_id' => $goaliTeam->id])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('goali.index'));
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
