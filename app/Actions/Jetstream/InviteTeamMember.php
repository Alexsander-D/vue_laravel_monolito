<?php

namespace App\Actions\Jetstream;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Events\InvitingTeamMember;
use App\Support\Jetstream;
use App\Mail\TeamInvitationMail;

class InviteTeamMember implements InvitesTeamMembers
{
    /**
     * Invite a new team member to the given team.
     */
    public function invite(User $user, Team $team, string $email, string $role = null): void
    {
        // Verifica se o usuário tem permissão para adicionar membros à equipe
        Gate::forUser($user)->authorize('addTeamMember', $team);

        // Validação da equipe, e-mail e papel/role (se necessário)
        $this->validate($team, $email, $role);

        // Despacha um evento para o convite do membro da equipe
        InvitingTeamMember::dispatch($team, $email, $role);

        // Criação da invitação na base de dados
        $invitation = $team->teamInvitations()->create([
            'email' => $email,
            'role' => $role,
        ]);

        // Envio do e-mail de convite usando o Mailable TeamInvitation
        Mail::to($email)->send(new TeamInvitationMail($invitation, $team));
    }

    /**
     * Validate the invite member operation.
     */
    protected function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], $this->rules($team), [
            'email.unique' => __('This user has already been invited to the team.'),
        ])->after(
            $this->ensureUserIsNotAlreadyOnTeam($team, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * Get the validation rules for inviting a team member.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function rules(Team $team): array
    {
        return array_filter([
            'email' => [
                'required',
                'email',
                Rule::unique(Jetstream::teamInvitationModel())->where(function (Builder $query) use ($team) {
                    $query->where('team_id', $team->id);
                }),
            ],
            // aqui você usa o Spatie direto, sem Jetstream
            'role' => ['required', 'string', Jetstream::hasRoles()],
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
                __('This user already belongs to the team.')
            );
        };
    }
}
