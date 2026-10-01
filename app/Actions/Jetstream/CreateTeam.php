<?php

namespace App\Actions\Jetstream;

use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;
use App\Support\Jetstream;

class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', 'unique:teams,name'],
        ])->validateWithBag('createTeam');

        AddingTeam::dispatch($user);

        return DB::transaction(function () use ($user, $input): Team {
            $team = $user->ownedTeams()->create([
                'name' => $input['name'],
                'personal_team' => true,
            ]);

            $adminRole = Role::create([
                'name' => 'Admin',
                'team_id' => $team->id,
            ]);

            Role::create([
                'name' => 'Espectador',
                'team_id' => $team->id,
            ]);

            $team->users()->attach($user->id, ['role' => $adminRole->name]);

            $permissionIds = [];
            foreach (['gerir-equipe', 'gerir-permissoes'] as $permissionName) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'team_id' => $team->id,
                ]);

                $permissionIds[] = $permission->id;
            }

            $adminRole->permissions()->sync($permissionIds);
            $user->switchTeam($team);

            return $team;
        });
    }
}
