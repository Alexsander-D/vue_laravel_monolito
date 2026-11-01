<?php

namespace Tests\Feature;

use App\Models\GoaliTrip;
use App\Models\Spatie\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GoaliTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_the_datetime_local_value_as_date_and_time(): void
    {
        $user = User::create([
            'name' => 'Motorista Teste',
            'email' => 'motorista@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)
            ->from('/goali/form')
            ->post(route('goali.store'), [
                'travel_datetime' => '2026-10-31T23:45',
                'status' => 'ENTRADA',
                'solicitation' => 'IMB',
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
            'solicitation' => 'IMB',
            'origin' => 'Origem informada',
            'destination' => 'Destino informado',
            'passenger' => 'Passageiro Teste',
            'responsible' => 'Motorista Teste',
            'amount' => '125.50',
        ]);
    }
}
