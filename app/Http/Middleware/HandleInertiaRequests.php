<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Normalize redirects after state-changing Inertia requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = parent::handle($request, $next);

        if (
            $request->header('X-Inertia') &&
            $response->getStatusCode() === 302 &&
            in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
        ) {
            $response->setStatusCode(303);
        }

        return $response;
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = Auth::user();

        return [
            ...parent::share($request),

            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            'flash' => fn() => [
                'message' => $request->session()->get('message'),
            ],

            'userRole' => $user ? $user->getRoleByUser() : null,
            'userPermissions' => $user ? $user->getPermissionsInTeamByUser() : [],
        ];
    }
}
