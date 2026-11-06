<?php

namespace App\Http\Controllers\Spatie;

use App\Models\Spatie\Role;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use App\Support\Jetstream;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Actions\UpdateTeamMemberRole;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\Features;

class TeamMemberController extends Controller
{
    /**
     * Add a new team member to a team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $teamId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);
        $email = $request->email ?: '';

        if (Features::sendsTeamInvitations() && !$request->filled('user_id')) {
            app(InvitesTeamMembers::class)->invite(
                $request->user(),
                $team,
                $email,
                $request->role
            );
        } else {
            if ($request->filled('user_id')) {
                $validated = $request->validateWithBag('addTeamMember', [
                    'user_id' => ['required', 'integer', 'exists:users,id'],
                ]);
                $selectedUser = User::findOrFail($validated['user_id']);
                $unassignedTeam = Team::whereName('EQUIPE NÃO ATRIBUÍDA')->first();

                Validator::make([], [])->after(function ($validator) use ($selectedUser, $unassignedTeam, $team) {
                    if (
                        !$unassignedTeam
                        || $unassignedTeam->id === $team->id
                        || !$selectedUser->teams()->whereKey($unassignedTeam->id)->exists()
                        || $team->users()->whereKey($selectedUser->id)->exists()
                    ) {
                        $validator->errors()->add('user_id', 'Selecione um usuário do time não atribuído.');
                    }
                })->validateWithBag('addTeamMember');

                $email = $selectedUser->email;
            } elseif (!$request->filled('email')) {
                $request->validateWithBag('addTeamMember', [
                    'user_id' => ['required'],
                ]);
            }

            app(AddsTeamMembers::class)->add(
                $request->user(),
                $team,
                $email,
                $request->role
            );
        }

        return back(303);
    }

    /**
     * Update the given team member's role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $teamId, $userId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);

        $role = Role::where('name', $request->role)
            ->where('team_id', $team->id)
            ->firstOrFail();

        $team->users()->updateExistingPivot($userId, ['role' => $role->name]);

        return back(303);
    }

    /**
     * Remove the given user from the given team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $teamId, $userId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);

        app(RemovesTeamMembers::class)->remove(
            $request->user(),
            $team,
            $user = Jetstream::findUserByIdOrFail($userId)
        );

        if ($request->user()->id === $user->id) {
            return redirect(config('fortify.home'));
        }

        return back(303);
    }
}
