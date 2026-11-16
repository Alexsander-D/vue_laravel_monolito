<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;
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

        if ($user?->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            $user->setAttribute(
                'profile_photo_url',
                '/storage/' . ltrim($user->profile_photo_path, '/'),
            );
        } elseif ($user) {
            $user->setAttribute('profile_photo_url', null);
        }

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
