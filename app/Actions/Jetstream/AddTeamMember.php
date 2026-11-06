<?php

namespace App\Actions\Jetstream;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Laravel\Jetstream\Jetstream;

class AddTeamMember implements AddsTeamMembers
{
    /**
     * Remove team member from the default team.
     */
    public function detachDefaultTeam(User $user)
    {
        $defaultTeam = Team::where('name', 'EQUIPE NÃO ATRIBUÍDA')->first();

        if ($defaultTeam) {
            $user->teams()->detach($defaultTeam->id);
        }
    }

    /**
     * Add a new team member to the given team.
     */
    public function add(User $user, Team $team, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        // Busca o novo membro do time
        $newTeamMember = User::where('email', $email)->firstOrFail();

        DB::transaction(function () use ($newTeamMember, $team, $role) {
            if ($newTeamMember->id !== 1) {
                $this->detachDefaultTeam($newTeamMember);
            }

            $team->users()->attach($newTeamMember->id, ['role' => $role]);

            $newTeamMember->current_team_id = $team->id;
            $newTeamMember->save();
        });

        TeamMemberAdded::dispatch($team, $newTeamMember);
    }

    /**
     * Validate the add member operation.
     */
    protected function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], $this->rules(), [
            'email.exists' => __('Não foi possível encontrar um usuário registrado com este endereço de e-mail.'),
            'role.required' => __('Selecione também a função do colaborador.'),
        ])->after(
            $this->ensureUserIsNotAlreadyOnTeam($team, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * Get the validation rules for adding a team member.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function rules(): array
    {
        return array_filter([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['nullable', 'string', Jetstream::hasRoles()],
        ]);
    }


    /**
     * Ensure that the user is not already on the team.
     */
    protected function ensureUserIsNotAlreadyOnTeam(Team $team, string $email): Closure
    {
        return function ($validator) use ($team, $email) {
            $validator->errors()->addIf(
                $team->hasUserWithEmail($email),
                'email',
                __('Este usuário já pertence à equipe.')
            );
        };
    }
}
