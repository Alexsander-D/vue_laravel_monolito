<?php

namespace App\Actions\Jetstream;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Closure;
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
        // Carrega explicitamente as relações dos times do usuário
        $user->load('teams');

        // Encontra o time padrão
        $defaultTeam = Team::where('name', 'EQUIPE NÃO ATRIBUÍDA')->first();

        if ($defaultTeam && $user->teams->contains($defaultTeam->id)) {
            // Remove o usuário do time padrão
            $user->teams()->detach($defaultTeam->id);
        }
    }

    /**
     * Add a new team member to the given team.
     */
    public function add(User $user, Team $team, string $email, string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        // Busca o novo membro do time
        $newTeamMember = Jetstream::findUserByEmailOrFail($email);

        // Remove o novo membro do time padrão, se aplicável
        if ($newTeamMember->id !== 1) {
            $this->detachDefaultTeam($newTeamMember);
        }

        // Adiciona o novo membro ao time atual
        $team->users()->attach(
            $newTeamMember->id,
            ['role' => $role]
        );

        // Define o time atual do usuário para o time ao qual foi adicionado
        $newTeamMember->current_team_id = $team->id;
        $newTeamMember->save();

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
