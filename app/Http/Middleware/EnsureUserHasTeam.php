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
                $userTeam = $user->teams()
                    ->where('teams.name', '!=', 'EQUIPE NÃO ATRIBUÍDA')
                    ->first()
                    ?? $user->teams()->first();

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

            if ($this->isUnassignedTeam($user->currentTeam) && ! $this->canAccessWithoutAssignedTeam($request)) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }

    private function canAccessWithoutAssignedTeam(Request $request): bool
    {
        return $request->routeIs([
            'dashboard',
            'logout',
            'current-team.update',
        ]);
    }

    private function isUnassignedTeam(?Team $team): bool
    {
        if (! $team) {
            return false;
        }

        $name = strtoupper(trim((string) $team->name));
        $name = strtr($name, [
            'ÃÂÃÂÃÂÃÂÃÂÃÂÃÂÃÂ' => 'A',
            'ÃÂÃÂÃÂÃÂÃÂÃÂÃÂÃÂ' => 'A',
            'ÃÂÃÂÃÂÃÂÃÂÃÂÃÂÃÂ' => 'I',
            'ÃÂÃÂÃÂÃÂÃÂÃÂÃÂÃÂ' => 'O',
        ]);

        return str_contains($name, 'EQUIPE')
            && str_contains($name, 'ATRIBU');
    }
}
