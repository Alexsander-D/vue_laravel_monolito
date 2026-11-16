<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Spatie\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    public function test_sharing_inertia_props_does_not_make_profile_photo_url_a_dirty_user_attribute(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/avatar.jpg', 'photo');

        $user = new User;
        $user->name = 'Test User';
        $user->profile_photo_path = 'profile-photos/avatar.jpg';

        Auth::setUser($user);

        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => $user);

        app(HandleInertiaRequests::class)->share($request);

        $this->assertArrayNotHasKey('profile_photo_url', $user->getDirty());
    }
}
