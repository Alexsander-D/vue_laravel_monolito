<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
/**
 * Redirect to the login page
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\RedirectResponse
 */
    public function toResponse($request)
    {
        // Inertia finaliza a requisição no cliente e o navegador comum precisa
        // receber uma navegação HTTP normal. Não devolva uma resposta JSON para
        // requisições Inertia, pois isso deixa o logout sem destino e pode gerar
        // uma segunda navegação com estado de sessão inconsistente.
        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->noContent();
        }

        return redirect()->route(
            'login',
            [],
            $request->header('X-Inertia') ? 303 : 302,
        );
    }
}
