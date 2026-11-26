<?php

namespace Tests\Feature;

use App\Models\GoaliTrip;
use App\Models\GoaliCompany;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoaliTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrativo_can_create_for_self_and_drivers_and_view_all_trips(): void
    {
        $administrativo = User::create([
            'name' => 'Usuário Administrativo',
            'email' => 'administrativo-goali@example.com',
            'password' => Hash::make('password'),
        ]);
        $administrativo->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($administrativo, 'Administrativo');

        $driver = User::create([
            'name' => 'Motorista Goali',
            'email' => 'motorista-administrativo@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->teams()->attach($team->id, ['role' => 'Motorista']);

        $otherAdmin = User::create([
            'name' => 'Outro Admin',
            'email' => 'outro-admin-goali@example.com',
            'password' => Hash::make('password'),
        ]);
        $otherAdmin->teams()->attach($team->id, ['role' => 'Admin']);

        $this->actingAs($administrativo)
            ->get(route('goali.form'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isAdmin', false)
                ->where('isAdministrative', true)
                ->where('canAssignDrivers', true)
                ->where('drivers', fn ($drivers) => collect($drivers)->pluck('id')->sort()->values()->all()
                    === collect([$administrativo->id, $driver->id, $otherAdmin->id])->sort()->values()->all()));

        $payload = [
            'travel_start_date' => now()->addDay()->toDateString(),
            'travel_end_date' => now()->addDay()->toDateString(),
            'travel_time' => '09:30',
            'status' => 'ENTRADA',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Passageiro Administrativo',
            'observation' => 'Observação de teste',
            'responsible' => 'Campo não confiável',
            'amount' => '50.00',
        ];

        $this->post(route('goali.store'), [...$payload, 'driver_id' => $driver->id])
            ->assertRedirect(route('goali.index'));
        $this->assertDatabaseHas('goali_trips', [
            'passenger' => 'Passageiro Administrativo',
            'driver_id' => $driver->id,
            'responsible' => $administrativo->name,
            'trip_status' => 'Criada',
            'observation' => 'Observação de teste',
        ]);

        $this->from(route('goali.form'))
            ->post(route('goali.store'), [...$payload, 'driver_id' => $administrativo->id])
            ->assertRedirect(route('goali.form'))
            ->assertSessionHasErrors('travel_date');

        $this->post(route('goali.store'), [
            ...$payload,
            'origin' => 'Outra origem',
            'driver_id' => $administrativo->id,
        ])
            ->assertRedirect(route('goali.index'));
        $this->assertDatabaseHas('goali_trips', [
            'passenger' => 'Passageiro Administrativo',
            'driver_id' => $administrativo->id,
            'trip_status' => 'Em Andamento',
        ]);

        $this->post(route('goali.store'), [
            ...$payload,
            'origin' => 'Corrida sem motorista',
            'driver_id' => null,
        ])->assertRedirect(route('goali.index'));
        $this->assertDatabaseHas('goali_trips', [
            'origin' => 'Corrida sem motorista',
            'driver_id' => null,
            'trip_status' => 'Em Andamento',
        ]);

        $outsider = User::create([
            'name' => 'Usuário externo',
            'email' => 'usuario-externo-goali@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->from(route('goali.form'))
            ->post(route('goali.store'), [...$payload, 'origin' => 'Origem externa', 'driver_id' => $outsider->id])
            ->assertRedirect(route('goali.form'))
            ->assertSessionHasErrors('driver_id');

        $createTrip = fn (int $driverId, string $responsible, string $status = 'ENTRADA', ?string $date = null) => GoaliTrip::create([
            'user_id' => $otherAdmin->id,
            'driver_id' => $driverId,
            'travel_date' => $date ?? now()->toDateString(),
            'travel_time' => '12:00',
            'status' => $status,
            'trip_status' => 'Pago',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida de teste',
            'responsible' => $responsible,
            'amount' => '10.00',
        ]);
        $assignedToAdministrative = $createTrip($administrativo->id, $otherAdmin->name);
        $responsibleForTrip = $createTrip($otherAdmin->id, $administrativo->name);
        $createTrip($administrativo->id, $otherAdmin->name, 'SAÍDA');
        $tripForAnotherResponsible = $createTrip($otherAdmin->id, $otherAdmin->name);
        $createTrip(
            $administrativo->id,
            $otherAdmin->name,
            'ENTRADA',
            now()->subDay()->toDateString()
        );

        $this->get(route('goali.index', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'status' => 'ENTRADA',
            'tripStatus' => 'Pago',
            'solicitation' => 'IMB',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.count', 3)
                ->where('records', fn ($records) => collect($records)->pluck('id')->sort()->values()->all()
                    === collect([$assignedToAdministrative->id, $responsibleForTrip->id, $tripForAnotherResponsible->id])->sort()->values()->all()));

        $this->get(route('goali.index', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'driverId' => $administrativo->id,
            'status' => 'ENTRADA',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewAllTrips', true)
                ->where('filters.driverId', (string) $administrativo->id)
                ->where('summary.count', 1)
                ->where('records.0.id', $assignedToAdministrative->id));

        $this->get(route('goali.index', [
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'responsible' => $otherAdmin->name,
            'status' => 'ENTRADA',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.responsible', $otherAdmin->name)
                ->where('summary.count', 2)
                ->where('responsibles', fn ($values) => $values->contains($otherAdmin->name)));
    }

    public function test_creating_a_role_redirects_to_the_updated_roles_page(): void
    {
        $user = User::create([
            'name' => 'Admin de Funções',
            'email' => 'admin-roles@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $team = new Team([
            'name' => 'Equipe de Funções',
            'personal_team' => false,
        ]);
        $team->user_id = $user->id;
        $team->save();
        $user->teams()->attach($team->id, ['role' => 'Admin']);
        $user->forceFill(['current_team_id' => $team->id])->save();

        $this->actingAs($user)
            ->put(route('roles.store'), ['roleInput' => 'Administrativo'])
            ->assertRedirect(route('roles.create'));

        $this->get(route('roles.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('rolesWithPermissions', fn ($roles) => collect($roles)->contains(
                    fn ($item) => $item['role']['name'] === 'Administrativo'
                )));
    }

    public function test_goali_permissions_migration_sets_the_expected_roles(): void
    {
        $user = User::create([
            'name' => 'Admin Migração',
            'email' => 'admin-permissions-migration@example.com',
            'password' => Hash::make('password'),
        ]);
        $team = new Team([
            'name' => 'GOALI TRANSPORTES',
            'personal_team' => false,
        ]);
        $team->user_id = $user->id;
        $team->save();

        $migration = require base_path('database/migrations/2026_11_16_000000_sync_goali_role_permissions.php');
        $migration->up();

        $adminPermissions = Role::where('team_id', $team->id)
            ->where('name', 'Admin')
            ->firstOrFail()
            ->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
        $driverPermissions = Role::where('team_id', $team->id)
            ->where('name', 'Motorista')
            ->firstOrFail()
            ->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
        $administrativoPermissions = Role::where('team_id', $team->id)
            ->where('name', 'Administrativo')
            ->firstOrFail()
            ->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertContains('goali-visualizar-todas-corridas', $adminPermissions);
        $this->assertContains('goali-excluir-corridas', $adminPermissions);
        $this->assertContains('goali-atualizar-status', $driverPermissions);
        $this->assertContains('goali-cancelar-corridas', $driverPermissions);
        $this->assertSame([
            'goali-criar-corridas',
            'goali-criar-para-motoristas',
            'goali-visualizar-corridas',
            'goali-visualizar-todas-corridas',
        ], $administrativoPermissions);
    }

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
        $firstDateTime = now()->addDay()->setTime(23, 45);
        $secondDateTime = now()->addDays(2)->setTime(9, 0);
        $updatedDate = now()->addDays(3)->toDateString();

        $this->actingAs($user)
            ->get(route('goali.form'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goali/Form')
                ->where('initialTripStatus', 'Em Andamento')
                ->where('currentUserId', $user->id)
                ->where('drivers', fn ($drivers) => collect($drivers)->pluck('id')->sort()->values()->all()
                    === collect([$driver->id, $user->id, $outsider->id])->sort()->values()->all()));

        $this->actingAs($user)
            ->post(route('goali.store'), [
                'travel_datetime' => $firstDateTime->format('Y-m-d\TH:i'),
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'trip_code' => 'GOALI-2026-001',
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
            'travel_date' => $firstDateTime->toDateString(),
            'travel_time' => '23:45',
            'status' => 'ENTRADA',
            'trip_status' => 'Criada',
            'driver_id' => $driver->id,
            'solicitation' => 'IMB',
            'trip_code' => 'GOALI-2026-001',
            'origin' => 'Origem informada',
            'destination' => 'Destino informado',
            'passenger' => 'Passageiro Teste',
            'responsible' => 'Operador Teste',
            'amount' => '125.50',
        ]);

        $this->actingAs($user)
            ->post(route('goali.store'), [
                'travel_datetime' => $secondDateTime->format('Y-m-d\TH:i'),
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
            'travel_date' => $updatedDate,
            'travel_time' => '08:30',
            'status' => 'SAÍDA',
            'solicitation' => 'IMB',
            'trip_code' => 'GOALI-2026-002',
            'driver_id' => $driver->id,
            'origin' => 'Origem atualizada',
            'destination' => 'Destino atualizado',
            'passenger' => 'Passageiro atualizado',
            'responsible' => 'Operador Teste',
            'amount' => '150.00',
            'company_profit' => '25.00',
        ])->assertRedirect();

        $this->assertDatabaseHas('goali_trips', [
            'id' => $trip->id,
            'travel_date' => $updatedDate,
            'travel_time' => '08:30',
            'status' => 'SAÍDA',
            'trip_status' => 'Criada',
            'driver_id' => $driver->id,
            'trip_code' => 'GOALI-2026-002',
            'origin' => 'Origem atualizada',
            'passenger' => 'Passageiro atualizado',
            'amount' => '150.00',
            'company_profit' => '25.00',
        ]);

        $this->assertDatabaseHas('goali_trips', ['id' => $trip->id]);
    }

    public function test_admin_can_mark_trips_cancelled_without_deleting_them(): void
    {
        $admin = User::create([
            'name' => 'Admin Goali',
            'email' => 'cancel-any-status@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($admin, 'Admin');

        foreach (['Criada', 'Em Andamento', 'Aguardando Aprovação', 'Pago'] as $status) {
            $trip = GoaliTrip::create([
                'user_id' => $admin->id,
                'driver_id' => $admin->id,
                'travel_date' => now()->toDateString(),
                'travel_time' => '12:00',
                'status' => 'ENTRADA',
                'trip_status' => $status,
                'solicitation' => 'IMB',
                'origin' => 'Origem',
                'destination' => 'Destino',
                'passenger' => "Corrida {$status}",
                'responsible' => $admin->name,
                'amount' => '10.00',
            ]);

            $this->actingAs($admin)
                ->put(route('goali.update', $trip), ['mark_cancelled' => true])
                ->assertRedirect();
            $this->assertDatabaseHas('goali_trips', [
                'id' => $trip->id,
                'trip_status' => 'Cancelada',
                'cancelled_by_name' => $admin->name,
            ]);
        }
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
        $firstRangeStart = now()->startOfMonth()->addMonths(2)->endOfMonth()->subDay();
        $firstRangeDates = collect(range(0, 3))
            ->map(fn (int $offset) => $firstRangeStart->copy()->addDays($offset)->toDateString())
            ->all();
        $thirtyDayMonth = now()->startOfMonth()->addMonth();
        while ($thirtyDayMonth->daysInMonth !== 30) {
            $thirtyDayMonth->addMonth();
        }
        $secondRangeStart = $thirtyDayMonth->copy()->day(30);
        $secondRangeDates = collect(range(0, 2))
            ->map(fn (int $offset) => $secondRangeStart->copy()->addDays($offset)->toDateString())
            ->all();

        $this->actingAs($user)
            ->post(route('goali.store'), [
                'travel_start_date' => $firstRangeDates[0],
                'travel_end_date' => $firstRangeDates[3],
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
        foreach ($firstRangeDates as $date) {
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
                'travel_start_date' => $secondRangeDates[0],
                'travel_end_date' => $secondRangeDates[2],
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
        foreach ($secondRangeDates as $date) {
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
        $startDate = now()->addDays(2)->toDateString();
        $endDate = now()->addDay()->toDateString();

        $this->actingAs($user)
            ->from('/goali/form')
            ->post(route('goali.store'), [
                'travel_start_date' => $startDate,
                'travel_end_date' => $endDate,
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

    public function test_it_rejects_creating_trips_in_past_dates(): void
    {
        $admin = User::create([
            'name' => 'Admin Data Passada',
            'email' => 'past-trip-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($admin, 'Admin');

        $payload = [
            'status' => 'ENTRADA',
            'solicitation' => 'IMB',
            'driver_id' => $admin->id,
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida em data passada',
            'responsible' => $admin->name,
            'amount' => '50.00',
            'travel_time' => '09:00',
        ];

        $this->actingAs($admin)
            ->from('/goali/form')
            ->post(route('goali.store'), [
                ...$payload,
                'travel_date' => now()->subDay()->toDateString(),
            ])
            ->assertRedirect('/goali/form')
            ->assertSessionHasErrors('travel_date');

        $this->post(route('goali.store'), [
            ...$payload,
            'travel_start_date' => now()->subDay()->toDateString(),
            'travel_end_date' => now()->toDateString(),
        ])
            ->assertRedirect('/goali/form')
            ->assertSessionHasErrors('travel_start_date');

        $this->assertDatabaseCount('goali_trips', 0);
    }

    public function test_drivers_can_create_trips_for_yesterday_but_not_earlier(): void
    {
        $driver = User::create([
            'name' => 'Motorista Data Retroativa',
            'email' => 'driver-backdated-trip@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($driver, 'Motorista');

        $payload = [
            'status' => 'ENTRADA',
            'solicitation' => 'Empresa Local',
            'driver_id' => $driver->id,
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => 'Corrida retroativa',
            'responsible' => $driver->name,
            'amount' => '50.00',
            'travel_time' => '09:15',
        ];

        $this->actingAs($driver)
            ->get(route('goali.form'))
            ->assertInertia(fn (Assert $page) => $page->where('isDriver', true));

        $this->post(route('goali.store'), [
            ...$payload,
            'travel_start_date' => now()->subDay()->toDateString(),
            'travel_end_date' => now()->subDay()->toDateString(),
        ])->assertRedirect(route('goali.index'));

        $this->assertDatabaseHas('goali_trips', [
            'driver_id' => $driver->id,
            'travel_date' => now()->subDay()->toDateString(),
            'passenger' => 'Corrida retroativa',
        ]);

        $this->from('/goali/form')
            ->post(route('goali.store'), [
                ...$payload,
                'travel_start_date' => now()->subDays(2)->toDateString(),
                'travel_end_date' => now()->subDays(2)->toDateString(),
            ])
            ->assertRedirect('/goali/form')
            ->assertSessionHasErrors('travel_start_date');

        $this->assertDatabaseCount('goali_trips', 1);
    }

    public function test_it_filters_by_solicitations_from_trips_and_registered_companies(): void
    {
        $admin = User::create([
            'name' => 'Admin Filtro Dinâmico',
            'email' => 'admin-dynamic-solicitation@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($admin, 'Admin');
        GoaliCompany::create([
            'team_id' => $team->id,
            'name' => 'Empresa cadastrada',
        ]);

        $createTrip = fn (string $solicitation, string $passenger) => GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => $admin->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Pago',
            'solicitation' => $solicitation,
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => $passenger,
            'responsible' => $admin->name,
            'amount' => '10.00',
        ]);
        $matchingTrip = $createTrip('Empresa Local', 'Passageiro da empresa local');
        $createTrip('IMB', 'Passageiro de outra empresa');

        $this->actingAs($admin)
            ->get(route('goali.index', ['solicitation' => 'Empresa Local']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('solicitations', fn ($values) => $values->contains('Empresa Local')
                    && $values->contains('Empresa cadastrada'))
                ->where('filters.solicitation', ['Empresa Local'])
                ->where('summary.count', 1)
                ->where('records.0.id', $matchingTrip->id));
    }

    public function test_driver_filter_options_include_all_team_members_and_passenger_options_use_selected_period(): void
    {
        $admin = User::create([
            'name' => 'Admin Filtros por Período',
            'email' => 'admin-period-filters@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($admin, 'Admin');

        $driverInPeriod = User::create([
            'name' => 'Motorista no período',
            'email' => 'driver-in-filter-period@example.com',
            'password' => Hash::make('password'),
        ]);
        $driverInPeriod->teams()->attach($team->id, ['role' => 'Motorista']);

        $driverOutsidePeriod = User::create([
            'name' => 'Motorista fora do período',
            'email' => 'driver-outside-filter-period@example.com',
            'password' => Hash::make('password'),
        ]);
        $driverOutsidePeriod->teams()->attach($team->id, ['role' => 'Motorista']);
        GoaliCompany::create([
            'team_id' => $team->id,
            'name' => 'Empresa cadastrada fora do período',
        ]);

        $createTrip = fn (int $driverId, string $date, string $solicitation, string $passenger) => GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => $driverId,
            'travel_date' => $date,
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Pago',
            'solicitation' => $solicitation,
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => $passenger,
            'responsible' => $admin->name,
            'amount' => '10.00',
        ]);
        $matchingTrip = $createTrip($driverInPeriod->id, now()->toDateString(), 'Empresa do período', "Ana\nBruno");
        $createTrip($admin->id, now()->toDateString(), 'Outra empresa do período', 'Carlos');
        $createTrip($driverOutsidePeriod->id, now()->subDay()->toDateString(), 'Empresa fora do período', 'Passageiro antigo');

        $this->actingAs($admin)
            ->get(route('goali.index', [
                'startDate' => now()->toDateString(),
                'endDate' => now()->toDateString(),
                'passenger' => 'Ana',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('solicitations', [
                    'Empresa cadastrada fora do período',
                    'Empresa do período',
                    'Empresa fora do período',
                    'Outra empresa do período',
                ])
                ->where('passengers', ['Ana', 'Bruno', 'Carlos'])
                ->where('filterDrivers', fn ($drivers) => collect($drivers)->pluck('id')->sort()->values()->all()
                    === collect([$admin->id, $driverInPeriod->id, $driverOutsidePeriod->id])->sort()->values()->all())
                ->where('filters.passenger', 'Ana')
                ->where('summary.count', 1)
                ->where('records.0.id', $matchingTrip->id));
    }

    public function test_it_filters_and_sorts_trips_by_date_and_time(): void
    {
        $user = User::create([
            'name' => 'Relatório Teste',
            'email' => 'report@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($user, 'Admin');

        $createTrip = fn (string $time, string $status) => GoaliTrip::create([
            'user_id' => $user->id,
            'driver_id' => $user->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => $time,
            'status' => $status,
            'trip_status' => $status === 'ENTRADA' ? 'Pago' : 'Em Andamento',
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
                'tripStatus' => 'Pago',
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
            'tripStatus' => 'Pago',
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

    public function test_only_admins_can_filter_goali_trips_by_driver(): void
    {
        $admin = User::create([
            'name' => 'Admin Filtro Motorista',
            'email' => 'admin-driver-filter@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $team = $this->attachToGoaliTeam($admin, 'Admin');

        $driver = User::create([
            'name' => 'Motorista Filtro',
            'email' => 'driver-filter@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->teams()->attach($team->id, ['role' => 'Motorista']);
        $driver->forceFill([
            'email_verified_at' => now(),
            'current_team_id' => $team->id,
        ])->save();

        $createTrip = fn (?int $driverId, string $passenger) => GoaliTrip::create([
            'user_id' => $admin->id,
            'driver_id' => $driverId,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Pago',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => $passenger,
            'responsible' => $admin->name,
            'amount' => '10.00',
        ]);
        $createTrip($driver->id, 'Corrida do motorista');
        $createTrip($admin->id, 'Corrida do administrador');
        $createTrip(null, 'Corrida sem motorista');

        $this->actingAs($admin)
            ->get(route('goali.index', ['driverId' => $driver->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isAdmin', true)
                ->where('filters.driverId', (string) $driver->id)
                ->where('summary.count', 1)
                ->where('records.0.passenger', 'Corrida do motorista'));

    }

    public function test_non_admin_cannot_apply_the_driver_filter(): void
    {
        $driver = User::create([
            'name' => 'Motorista sem filtro',
            'email' => 'driver-without-filter@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($driver, 'Motorista');

        $otherDriver = User::create([
            'name' => 'Outro motorista',
            'email' => 'other-driver-filter@example.com',
            'password' => Hash::make('password'),
        ]);
        $team = $driver->currentTeam;
        $team->users()->attach($otherDriver->id, ['role' => 'Motorista']);

        $createTrip = fn (?int $driverId, string $passenger) => GoaliTrip::create([
            'user_id' => $driver->id,
            'driver_id' => $driverId,
            'travel_date' => now()->toDateString(),
            'travel_time' => '12:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Pago',
            'solicitation' => 'IMB',
            'origin' => 'Origem',
            'destination' => 'Destino',
            'passenger' => $passenger,
            'responsible' => $driver->name,
            'amount' => '10.00',
        ]);
        $createTrip($driver->id, 'Corrida própria');
        $createTrip($otherDriver->id, 'Corrida de outro motorista');
        $createTrip(null, 'Corrida sem motorista');

        $this->actingAs($driver)
            ->get(route('goali.index', ['driverId' => $otherDriver->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isAdmin', false)
                ->where('filters.driverId', '')
                ->where('summary.count', 2)
                ->where('summary', fn ($summary) => ! array_key_exists('companyProfit', $summary))
                ->where('records', fn ($records) => ! array_key_exists('company_profit', $records[0])));
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
            'trip_status' => 'Pago',
            'solicitation' => 'IMB',
            'origin' => 'Origem atribuída',
            'destination' => 'Destino',
            'passenger' => 'Corrida atribuída',
            'responsible' => $admin->name,
            'amount' => '75.00',
            'company_profit' => '25.00',
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
            'company_profit' => '10.00',
        ]);

        $this->actingAs($admin)
            ->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.count', 2)
                ->where('summary.amount', 75)
                ->where('summary.receivable', 75)
                ->where('summary.companyProfit', 35));
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

        $finishedTrip = $createTrip('Pago', '08:00', 'LOGVALE', 'SAÍDA');
        $awaitingTrip = $createTrip('Aguardando Aprovação', '09:00', 'FRANGO DOURADO', 'ENTRADA');
        $inProgressTrip = $createTrip('Em Andamento', '10:00', 'OSESP', 'SAÍDA');
        $createdTrip = $createTrip('Criada', '11:00', 'IMB', 'ENTRADA');

        $this->actingAs($admin)
            ->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.startDate', now()->startOfMonth()->toDateString())
                ->where('filters.endDate', now()->endOfMonth()->toDateString())
                ->where('records', fn ($records) => collect($records)->pluck('id')->all() === [
                    $createdTrip->id,
                    $inProgressTrip->id,
                    $awaitingTrip->id,
                    $finishedTrip->id,
                ]));

        $this->get(route('goali.index', [
            'status' => ['ENTRADA', 'SAÍDA'],
            'tripStatus' => ['Criada', 'Em Andamento', 'Aguardando Aprovação'],
            'solicitation' => ['IMB', 'OSESP', 'FRANGO DOURADO'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', ['ENTRADA', 'SAÍDA'])
                ->where('filters.tripStatus', ['Criada', 'Em Andamento', 'Aguardando Aprovação'])
                ->where('filters.solicitation', ['IMB', 'OSESP', 'FRANGO DOURADO'])
                ->where('records', fn ($records) => collect($records)->pluck('id')->all() === [
                    $createdTrip->id,
                    $inProgressTrip->id,
                    $awaitingTrip->id,
                ]));
    }

    public function test_driver_trip_starts_in_progress_and_sends_running_trips_for_approval(): void
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
        $inProgressStartedAt = $createdTrip->in_progress_started_at;
        $this->assertNotNull($inProgressStartedAt);

        $this->actingAs($driver, 'sanctum')
            ->put(route('goali.update', $createdTrip), ['trip_status' => 'Pago'])
            ->assertSessionHasErrors('trip_status');
        $this->put(route('goali.update', $createdTrip), [
            'trip_status' => 'Aguardando Aprovação',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Aguardando Aprovação', $createdTrip->fresh()->trip_status);
        $this->assertEquals($inProgressStartedAt, $createdTrip->fresh()->in_progress_started_at);

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
            'trip_status' => 'Aguardando Aprovação',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('Aguardando Aprovação', $runningTrip->fresh()->trip_status);

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
            'trip_status' => 'Aguardando Aprovação',
        ])->assertForbidden();
        $this->assertSame('Criada', $createdTrip->fresh()->trip_status);
        $this->put(route('goali.update', $createdTrip), [
            'trip_status' => 'Em Andamento',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Em Andamento', $createdTrip->fresh()->trip_status);
        $this->assertSame($driver->id, $createdTrip->fresh()->driver_id);
        $this->assertNotNull($createdTrip->fresh()->in_progress_started_at);
        $this->put(route('goali.update', $createdTrip), [
            'trip_status' => 'Aguardando Aprovação',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Aguardando Aprovação', $createdTrip->fresh()->trip_status);
        $this->assertNotNull($createdTrip->fresh()->in_progress_started_at);

        $this->put(route('goali.update', $runningTrip), [
            'trip_status' => 'Pago',
        ])->assertSessionHasErrors('trip_status');
        $this->delete(route('goali.update', $runningTrip))
            ->assertStatus(405);

        $driver->teams()->updateExistingPivot($team->id, ['role' => 'Admin']);

        $this
            ->get(route('goali.index'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.receivable', 0));

        $this
            ->put(route('goali.update', $runningTrip), $this->tripUpdatePayload($runningTrip, 'Pago'))
            ->assertRedirect();
        $this->assertSame('Pago', $runningTrip->fresh()->trip_status);

        $this
            ->get(route('goali.index'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.receivable', 75));
    }

    public function test_driver_can_create_a_trip_while_one_is_in_progress(): void
    {
        $driver = User::create([
            'name' => 'Motorista com corrida ativa',
            'email' => 'driver-active-trip@example.com',
            'password' => Hash::make('password'),
        ]);
        $driver->forceFill(['email_verified_at' => now()])->save();
        $this->attachToGoaliTeam($driver, 'Motorista');

        GoaliTrip::create([
            'user_id' => $driver->id,
            'driver_id' => $driver->id,
            'travel_date' => now()->toDateString(),
            'travel_time' => '09:00',
            'status' => 'ENTRADA',
            'trip_status' => 'Em Andamento',
            'solicitation' => 'IMB',
            'origin' => 'Origem ativa',
            'destination' => 'Destino ativo',
            'passenger' => 'Passageiro ativo',
            'responsible' => $driver->name,
            'amount' => '50.00',
        ]);

        $this->actingAs($driver)
            ->get(route('goali.form'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isDriver', true));

        $this->from('/goali/form')
            ->post(route('goali.store'), [
                'travel_datetime' => now()->format('Y-m-d\TH:i'),
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
                'driver_id' => null,
                'origin' => 'Nova origem',
                'destination' => 'Novo destino',
                'passenger' => 'Novo passageiro',
                'responsible' => $driver->name,
                'amount' => '30.00',
            ])
            ->assertRedirect('/goali/form')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('goali_trips', 2);
        $this->assertDatabaseHas('goali_trips', [
            'passenger' => 'Novo passageiro',
            'driver_id' => $driver->id,
        ]);
    }

    public function test_admin_can_mark_an_in_progress_trip_as_paid(): void
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

        $this->put(route('goali.update', $trip), [
            ...$this->tripUpdatePayload($trip),
            'origin' => 'Origem atualizada',
            'trip_status' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Criada', $trip->fresh()->trip_status);
        $this->assertSame('Origem atualizada', $trip->fresh()->origin);

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip, 'Em Andamento'))
            ->assertRedirect();
        $this->assertSame('Em Andamento', $trip->fresh()->trip_status);
        $inProgressStartedAt = $trip->fresh()->in_progress_started_at;
        $this->assertNotNull($inProgressStartedAt);

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip, 'Pago'))
            ->assertRedirect();
        $this->assertSame('Pago', $trip->fresh()->trip_status);
        $this->assertEquals($inProgressStartedAt, $trip->fresh()->in_progress_started_at);

        $this->put(route('goali.update', $trip), $this->tripUpdatePayload($trip))
            ->assertForbidden();
    }

    public function test_admin_can_cancel_an_approval_trip_back_to_the_unassigned_queue(): void
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
            'trip_status' => 'Aguardando Aprovação',
            'in_progress_started_at' => now()->subMinute(),
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
        $this->assertNull($trip->fresh()->in_progress_started_at);
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

    public function test_admin_can_create_an_unassigned_trip_that_starts_in_progress(): void
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
        $this->assertSame('Em Andamento', $trip->trip_status);
        $this->assertNotNull($trip->in_progress_started_at);

        $admin->teams()->updateExistingPivot($team->id, ['role' => 'Motorista']);

        $this->get(route('goali.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('currentDriverId', $admin->id)
                ->where('records.0.id', $trip->id));

        $this->put(route('goali.update', $trip), [
            'trip_status' => 'Aguardando Aprovação',
            'driver_id' => $admin->id,
        ])->assertRedirect();

        $this->assertSame($admin->id, $trip->fresh()->driver_id);
        $this->assertSame('Aguardando Aprovação', $trip->fresh()->trip_status);

    }

    public function test_driver_creation_automatically_assigns_the_driver_to_himself(): void
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
            'passenger' => 'Corrida atribuída automaticamente',
                'responsible' => $driver->name,
                'amount' => '100.00',
            ])
        ->assertRedirect('/goali/form')
        ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('goali_trips', [
        'passenger' => 'Corrida atribuída automaticamente',
        'driver_id' => $driver->id,
        ]);
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

    public function test_dashboard_never_renders_barber_dashboard_for_a_goali_user_with_an_old_current_team(): void
    {
        $user = User::create([
            'name' => 'Usuário Goali com equipe antiga',
            'email' => 'goali-old-team@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $barberTeam = new Team([
            'name' => 'BARBEARIA CARIOCA 2',
            'personal_team' => false,
        ]);
        $barberTeam->user_id = $user->id;
        $barberTeam->save();
        $user->teams()->attach($barberTeam->id, ['role' => 'Admin']);

        $goaliTeam = new Team([
            'name' => 'GOALI TRANSPORTES',
            'personal_team' => false,
        ]);
        $goaliTeam->user_id = $user->id;
        $goaliTeam->save();
        $user->teams()->attach($goaliTeam->id, ['role' => 'Admin']);
        $user->forceFill(['current_team_id' => $barberTeam->id])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('goali.index'));

        $this->assertSame($goaliTeam->id, $user->fresh()->current_team_id);
    }

    private function attachToGoaliTeam(User $user, string $role): Team
    {
        $team = new Team([
            'name' => 'GOALI TRANSPORTES',
            'personal_team' => false,
        ]);
        $team->user_id = $user->id;
        $team->save();

        $permissionsByRole = [
            'Motorista' => [
                'goali-visualizar-corridas',
                'goali-criar-corridas',
                'goali-atualizar-status',
                'goali-cancelar-corridas',
            ],
            'Admin' => [
                'goali-visualizar-corridas',
                'goali-visualizar-todas-corridas',
                'goali-criar-corridas',
                'goali-criar-para-motoristas',
                'goali-gerenciar-corridas',
                'goali-cancelar-corridas',
                'goali-excluir-corridas',
            ],
            'Administrativo' => [
                'goali-visualizar-corridas',
                'goali-visualizar-todas-corridas',
                'goali-criar-corridas',
                'goali-criar-para-motoristas',
            ],
        ];

        foreach ($permissionsByRole as $roleName => $permissionNames) {
            $roleModel = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'team_id' => $team->id,
            ]);
            $permissionIds = collect($permissionNames)->map(fn (string $permissionName) => Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
                'team_id' => $team->id,
            ])->id);
            $roleModel->permissions()->sync($permissionIds);
        }

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
