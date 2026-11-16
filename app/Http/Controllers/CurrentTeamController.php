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

        // O usuário recebe o accessor profile_photo_url no middleware do Inertia.
        // Usar save() aqui tentaria gravar esse atributo anexado na tabela users.
        User::query()
            ->whereKey($user->getKey())
            ->update(['current_team_id' => $team->id]);
        $user->current_team_id = $team->id;
        $user->setRelation('currentTeam', $team);

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect(config('fortify.home'), 303);
    }
}
