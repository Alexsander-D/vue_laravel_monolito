<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_created_as_email_verified(): void
    {
        $owner = $this->createUser('owner@example.com');
        $defaultTeam = new Team([
            'name' => 'EQUIPE NÃO ATRIBUÍDA',
            'personal_team' => false,
        ]);
        $defaultTeam->user_id = $owner->id;
        $defaultTeam->save();

        $user = app(CreateNewUser::class)->create([
            'name' => 'Novo usuário',
            'email' => 'novo@example.com',
            'password' => 'CurrentPass123!',
            'password_confirmation' => 'CurrentPass123!',
            'terms' => true,
        ]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_authenticated_user_can_change_password_with_current_password(): void
    {
        $user = $this->createUser('change-password@example.com');

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'CurrentPass123!',
                'password' => 'NewPassword456!',
                'password_confirmation' => 'NewPassword456!',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword456!', $user->fresh()->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = $this->createUser('wrong-password@example.com');

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'WrongCurrentPass!',
                'password' => 'NewPassword456!',
                'password_confirmation' => 'NewPassword456!',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('CurrentPass123!', $user->fresh()->password));
    }

    public function test_password_reset_updates_password_and_rotates_remember_token(): void
    {
        $user = $this->createUser('reset-password@example.com');
        $user->forceFill(['remember_token' => 'previous-remember-token'])->save();

        app(ResetUserPassword::class)->reset($user, [
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $updatedUser = $user->fresh();

        $this->assertTrue(Hash::check('NewPassword456!', $updatedUser->password));
        $this->assertNotSame('previous-remember-token', $updatedUser->remember_token);
    }

    public function test_forgot_password_sends_a_reset_notification(): void
    {
        Notification::fake();
        $user = $this->createUser('forgot-password@example.com');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    private function createUser(string $email): User
    {
        $user = User::create([
            'name' => 'Usuário de teste',
            'email' => $email,
            'password' => Hash::make('CurrentPass123!'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
