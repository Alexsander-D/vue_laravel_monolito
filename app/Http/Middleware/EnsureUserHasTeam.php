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

            // Verifica se o usuário tem um currentTeam
            if (!$user->current_team_id) {
                // Verifica se o usuário já pertence a algum time
                $userTeam = $user->teams->first();

                if ($userTeam) {
                    // Se o usuário já pertence a algum time, define o current_team_id para esse time
                    $user->current_team_id = $userTeam->id;
                } else {
                    // Encontra o time padrão "EQUIPE NÃO ATRIBUÍDA"
                    $defaultTeam = Team::where('name', 'EQUIPE NÃO ATRIBUÍDA')->first();

                    if ($defaultTeam) {
                        // Associa o usuário ao time "EQUIPE NÃO ATRIBUÍDA" se ele ainda não pertence
                        if (!$user->teams->contains($defaultTeam->id)) {
                            $user->teams()->attach($defaultTeam->id, ['role' => 'Espectador']);
                        }
                        $user->current_team_id = $defaultTeam->id;
                    }
                }

                // Salva as alterações no usuário
                $user->save();

                // Redireciona para a mesma rota para garantir que o currentTeam seja recarregado corretamente
                return back();
            }
        }

        return $next($request);
    }
}
