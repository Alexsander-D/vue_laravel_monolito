<?php

namespace App\Http\Controllers\Spatie;

use App\Http\Controllers\Controller;
use App\Models\Spatie\Team;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LobbyController extends Controller
{
    /**
     * Exibe a tela de criação de times.
     *
     * @return \Inertia\Response
     */
    public function show()
    {
        $user = Auth::user();

        $teams = Team::leftJoin('team_user', 'teams.id', '=', 'team_user.team_id')
            ->where('team_user.user_id', $user->id)
            ->select('teams.id', 'teams.name', 'team_user.role')
            ->get()
            ->map(fn($team) => [
                'name' => $team->name,
                'role' => $team->role ?? 'FUNCAO NAO ATRIBUIDA',
            ]);

        return Inertia::render('Spatie/Lobby', [
            'teams' => $teams,
        ]);
    }
}
