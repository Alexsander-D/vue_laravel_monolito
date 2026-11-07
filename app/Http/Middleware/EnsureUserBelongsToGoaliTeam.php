<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserBelongsToGoaliTeam
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $belongsToGoali = $user?->teams->contains(
            fn ($team) => strcasecmp(trim($team->name), 'GOALI TRANSPORTES') === 0
        );

        abort_unless($belongsToGoali, 403);

        return $next($request);
    }
}
