<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureEmailIsVerified
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
        // Lista de rotas que podem passar mesmo sem verificação
        $except = [
            'verification.notice',
            'verification.send',
            'verification.verify',
        ];

        if (
            Auth::check() &&
            $request->user() instanceof MustVerifyEmail &&
            !$request->user()->hasVerifiedEmail() &&
            !$request->routeIs($except) // se a rota NÃO estiver na lista, redireciona
        ) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
