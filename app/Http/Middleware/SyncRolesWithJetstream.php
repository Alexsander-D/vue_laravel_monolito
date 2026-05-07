<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\Jetstream;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class SyncRolesWithJetstream
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) { // Verifica se o usuário está autenticado
            $user = Auth::user();

            if (!!$user->current_team_id) {
                // Obtenha as roles do banco de dados para o time atual
                $roles = Role::where('team_id', $user->current_team_id)->get();

                // Sincronize as roles com o Jetstream sem o parâmetro de descrição
                foreach ($roles as $role) {
                    // Limpa e padroniza o nome da role
                    $key = strtolower(trim($role->name));
                    $key = str_replace(' ', '_', $key);

                    // Obtém as permissões relacionadas à role
                    $permissions = $role->permissions->pluck('name')->all();

                    // Sincroniza com o Jetstream sem a descrição
                    Jetstream::role($role->name, $role->name, $permissions);
                }
            }
        }

        return $next($request);
    }
}
