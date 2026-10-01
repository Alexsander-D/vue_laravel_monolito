<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Spatie\Team;

class EnsureUserHasTeam
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$user->currentTeam) {
                $userTeam = $user->teams->first();

                if (!$userTeam) {
                    $userTeam = Team::where('name', 'EQUIPE NÃO ATRIBUÍDA')->first();

                    if ($userTeam) {
                        $user->teams()->attach($userTeam->id, ['role' => 'Espectador']);
                    }
                }

                if ($userTeam) {
                    $user->current_team_id = $userTeam->id;
                    $user->save();

                    return back();
                }
            }
        }

        return $next($request);
    }
}
