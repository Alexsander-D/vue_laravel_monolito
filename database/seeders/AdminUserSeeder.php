<?php

namespace Database\Seeders;

use App\Models\Spatie\Role;
use Illuminate\Database\Seeder;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate([
            'name' => 'Admin User',
            'email' => 'admin@hotmail.com',
            'password' => 'admin',
        ]);

        $user->markEmailAsVerified();

        $team = Team::firstOrCreate([
            'name' => 'EQUIPE NÃO ATRIBUÍDA',
            'personal_team' => true,
            'user_id' => $user->id,
        ]);

        Role::firstOrCreate([
            'name' => 'Admin',
            'team_id' => $team->id,
        ]);

        Role::firstOrCreate([
            'name' => 'Espectador',
            'team_id' => $team->id,
        ]);

        $user->teams()->attach($team->id, ['role' => 'Admin']);

        $user->current_team_id = $team->id;
        $user->save();
    }
}
