<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserPermission
{
    public function handle(Request $request, Closure $next, $permission = null)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Acesso negado.');
        }

        // Checa se o usuário tem a permissão passada
        if ($permission && !$user->getPermissionsInTeamByUser()->contains($permission)) {
            abort(403, 'Acesso negado.');
        }


        return $next($request);
    }
}
