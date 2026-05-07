<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Agent;
use Laravel\Jetstream\Http\Controllers\Inertia\Concerns\ConfirmsTwoFactorAuthentication;

class UserProfileController extends Controller
{
    use ConfirmsTwoFactorAuthentication;

    /**
     * Mostra a tela de configura o de perfil geral.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function show(Request $request)
    {
        $this->validateTwoFactorAuthenticationState($request);

        return Inertia::render('Spatie/Profile/Show', [
            'confirmsTwoFactorAuthentication' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'sessions' => $this->getSessions($request),
        ]);
    }

    /**
     * Obtem as sess oes atuais.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    protected function getSessions(Request $request): Collection
    {
        if (config('session.driver') !== 'database') {
            return Collection::empty();
        }

        return Collection::make(
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->orderByDesc('last_activity')
                ->get()
        )->map(function ($session) use ($request) {
            return (object) [
                'agent' => $this->getAgent($session),
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === $request->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        });
    }

    /**
     * Cria uma nova inst ncia de agente a partir da sess o dada.
     *
     * @param  mixed  $session
     * @return \Laravel\Jetstream\Agent
     */
    protected function getAgent($session): Agent
    {
        return tap(new Agent(), fn($agent) => $agent->setUserAgent($session->user_agent));
    }
}

