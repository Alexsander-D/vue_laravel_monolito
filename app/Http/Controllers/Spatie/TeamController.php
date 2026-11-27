<?php
namespace App\Http\Controllers\Spatie;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Actions\ValidateTeamDeletion;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\Contracts\UpdatesTeamNames;
use App\Support\Jetstream;
use App\Models\Spatie\Team;
use Laravel\Jetstream\RedirectsActions;

class TeamController extends Controller
{
    use RedirectsActions;

    /**
     * Show the team management screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Inertia\Response
     */
    public function show(Request $request, $teamId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);

        Gate::authorize('view', $team);
        $team->load('owner', 'users', 'teamInvitations');

        $isGoaliTeam = strcasecmp(trim($team->name), 'GOALI TRANSPORTES') === 0;
        $viewer = $team->users->firstWhere('id', $request->user()->id);
        $canManageGoaliDriverAssignments = $isGoaliTeam
            && (int) $request->user()->current_team_id === (int) $team->id
            && $viewer?->pivot?->role === 'Admin';
        $goaliDrivers = $canManageGoaliDriverAssignments
            ? $team->users
                ->filter(fn ($member) => $member->pivot->role === 'Motorista')
                ->map(fn ($driver) => ['id' => (int) $driver->id, 'name' => $driver->name])
                ->values()
                ->all()
            : [];
        $assignedDriverIdsByManager = $canManageGoaliDriverAssignments
            ? DB::table('goali_driver_assignments')
                ->where('team_id', $team->id)
                ->get(['manager_id', 'driver_id'])
                ->groupBy('manager_id')
                ->map(fn ($assignments) => $assignments->pluck('driver_id')->map(fn ($id) => (int) $id)->all())
            : collect();
        $goaliDriverManagers = $canManageGoaliDriverAssignments
            ? $team->users
                ->filter(fn ($member) => in_array($member->pivot->role, ['Admin', 'Administrativo'], true))
                ->map(fn ($manager) => [
                    'id' => (int) $manager->id,
                    'name' => $manager->name,
                    'role' => $manager->pivot->role,
                    'driverIds' => $assignedDriverIdsByManager->get($manager->id, []),
                ])
                ->values()
                ->all()
            : [];

        $unassignedTeam = Team::whereName('EQUIPE NÃO ATRIBUÍDA')->first();
        $unassignedUsers = $unassignedTeam && $unassignedTeam->id !== $team->id
            ? $unassignedTeam->users()
                ->whereDoesntHave('teams', fn ($query) => $query->where('teams.id', $team->id))
                ->get(['users.id', 'users.name', 'users.email'])
            : collect();

        return Jetstream::inertia()->render($request, 'Spatie/Teams/Show', [
            'team' => $team,
            'canManageGoaliDriverAssignments' => $canManageGoaliDriverAssignments,
            'goaliDriverManagers' => $goaliDriverManagers,
            'goaliDrivers' => $goaliDrivers,
            'unassignedUsers' => $unassignedUsers,
            'availableRoles' => array_values(Jetstream::$roles),
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
            'permissions' => [
                'canAddTeamMembers' => Gate::check('addTeamMember', $team),
                'canDeleteTeam' => Gate::check('delete', $team),
                'canRemoveTeamMembers' => Gate::check('removeTeamMember', $team),
                'canUpdateTeam' => Gate::check('update', $team),
                'canUpdateTeamMembers' => Gate::check('updateTeamMember', $team),
            ],
        ]);
    }

    /**
     * Show the team creation screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Jetstream::newTeamModel());

        return Jetstream::inertia()->render($request, 'Spatie/Teams/Create');
    }

    /**
     * Create a new team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $creator = app(CreatesTeams::class);

        $creator->create($request->user(), $request->all());

        return $this->redirectPath($creator);
    }

    /**
     * Update the given team's name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $teamId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);

        app(UpdatesTeamNames::class)->update($request->user(), $team, $request->all());

        return back(303);
    }

    /**
     * Delete the given team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $teamId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);

        app(ValidateTeamDeletion::class)->validate($request->user(), $team);

        $deleter = app(DeletesTeams::class);

        $deleter->delete($team);

        return $this->redirectPath($deleter);
    }
}
