<?php

namespace App\Http\Controllers\Spatie;

use App\Models\Spatie\Role;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use App\Support\Jetstream;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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

        if (strcasecmp(trim($team->name), 'GOALI TRANSPORTES') === 0) {
            if (! in_array($role->name, ['Admin', 'Administrativo'], true)) {
                DB::table('goali_driver_assignments')
                    ->where('team_id', $team->id)
                    ->where('manager_id', $userId)
                    ->delete();
            }

            if ($role->name !== 'Motorista') {
                DB::table('goali_driver_assignments')
                    ->where('team_id', $team->id)
                    ->where('driver_id', $userId)
                    ->delete();
            }
        }

        return back(303);
    }

    public function updateGoaliDriverAssignments(Request $request, $teamId, $userId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);
        $admin = $request->user();

        abort_unless(
            (int) $admin->current_team_id === (int) $team->id
                && strcasecmp(trim($team->name), 'GOALI TRANSPORTES') === 0
                && $team->users()
                    ->where('users.id', $admin->id)
                    ->wherePivot('role', 'Admin')
                    ->exists(),
            403
        );

        $manager = $team->users()->where('users.id', $userId)->firstOrFail();
        abort_unless(in_array($manager->pivot->role, ['Admin', 'Administrativo'], true), 404);

        $validated = $request->validate([
            'driver_ids' => ['present', 'array'],
            'driver_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $driverIds = array_map('intval', $validated['driver_ids']);
        $eligibleDriverIds = $team->users()
            ->wherePivot('role', 'Motorista')
            ->whereIn('users.id', $driverIds)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($eligibleDriverIds) !== count($driverIds)) {
            throw ValidationException::withMessages([
                'driver_ids' => 'Selecione somente motoristas desta equipe.',
            ]);
        }

        DB::transaction(function () use ($team, $manager, $driverIds): void {
            DB::table('goali_driver_assignments')
                ->where('team_id', $team->id)
                ->where('manager_id', $manager->id)
                ->delete();

            if ($driverIds) {
                $now = now();
                DB::table('goali_driver_assignments')->insert(array_map(
                    fn ($driverId) => [
                        'team_id' => $team->id,
                        'manager_id' => $manager->id,
                        'driver_id' => $driverId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $driverIds,
                ));
            }
        });

        return back(303)->with('success', 'Motoristas atribuidos com sucesso.');
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

        $user = Jetstream::findUserByIdOrFail($userId);
        app(RemovesTeamMembers::class)->remove($request->user(), $team, $user);

        DB::table('goali_driver_assignments')
            ->where('team_id', $team->id)
            ->where(fn ($query) => $query
                ->where('manager_id', $user->id)
                ->orWhere('driver_id', $user->id))
            ->delete();

        if ($request->user()->id === $user->id) {
            return redirect(config('fortify.home'));
        }

        return back(303);
    }
}
