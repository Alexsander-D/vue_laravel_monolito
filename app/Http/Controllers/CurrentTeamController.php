<?php

namespace App\Http\Controllers;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class CurrentTeamController extends Controller
{
    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $team = Team::query()->findOrFail($request->integer('team_id'));

        abort_unless($user->belongsToTeam($team), 403, 'Você não pertence a esta equipe.');

        $user->forceFill(['current_team_id' => $team->id])->save();
        $user->setRelation('currentTeam', $team);

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect(config('fortify.home'), 303);
    }
}
