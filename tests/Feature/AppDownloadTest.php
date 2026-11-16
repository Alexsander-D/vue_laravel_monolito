<?php

namespace Tests\Feature;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_page_is_only_available_to_verified_goali_team_members(): void
    {
        $user = $this->createUser('goali-member@example.com');
        $goaliTeam = $this->createTeam($user, 'goali transportes');
        $user->teams()->attach($goaliTeam->id, ['role' => 'Motorista']);
        $user->forceFill(['current_team_id' => $goaliTeam->id])->save();

        $this->actingAs($user, 'sanctum')
            ->get(route('app.download.page'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('App/Download'));
    }

    public function test_apk_download_rejects_users_outside_the_goali_team(): void
    {
        $outsider = $this->createUser('apk-outsider@example.com');

        $this->actingAs($outsider)
            ->get(route('app.download.page'))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('app.download'))
            ->assertForbidden();
    }

    public function test_download_routes_require_login_and_verified_email(): void
    {
        $this->get(route('app.download.page'))
            ->assertRedirect(route('login'));

        $user = $this->createUser('unverified-goali@example.com', verified: false);
        $team = $this->createTeam($user, 'GOALI TRANSPORTES');
        $user->teams()->attach($team->id, ['role' => 'Motorista']);

        $this->actingAs($user)
            ->get(route('app.download.page'))
            ->assertRedirect(route('verification.notice'));
    }

    private function createUser(string $email, bool $verified = true): User
    {
        $user = User::create([
            'name' => 'Usuário de teste',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        if ($verified) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    private function createTeam(User $owner, string $name): Team
    {
        $team = new Team([
            'name' => $name,
            'personal_team' => false,
        ]);
        $team->user_id = $owner->id;
        $team->save();

        return $team;
    }
}
