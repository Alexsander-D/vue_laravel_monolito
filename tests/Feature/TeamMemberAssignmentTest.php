<?php

namespace Tests\Feature;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamMemberAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_a_user_from_the_unassigned_team(): void
    {
        $admin = $this->createUser('admin@example.com');
        $team = $this->createTeam($admin, 'GOALI TRANSPORTES');
        $team->users()->attach($admin->id, ['role' => 'Admin']);
        $admin->forceFill(['current_team_id' => $team->id])->save();

        $unassignedTeam = $this->createTeam($admin, 'EQUIPE NÃO ATRIBUÍDA');
        $unassignedUser = $this->createUser('member@example.com');
        $unassignedTeam->users()->attach($unassignedUser->id, ['role' => 'Espectador']);

        $this->actingAs($admin)
            ->get(route('teams.show', $team))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('unassignedUsers.0.id', $unassignedUser->id)
                ->where('unassignedUsers.0.email', $unassignedUser->email));

        $this->actingAs($admin)
            ->post(route('team-members.store', $team), ['user_id' => $unassignedUser->id])
            ->assertRedirect();

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $unassignedUser->id,
        ]);
        $this->assertDatabaseMissing('team_user', [
            'team_id' => $unassignedTeam->id,
            'user_id' => $unassignedUser->id,
        ]);
        $this->assertSame($team->id, $unassignedUser->fresh()->current_team_id);
    }

    public function test_assignment_rejects_users_outside_the_unassigned_team(): void
    {
        $admin = $this->createUser('admin@example.com');
        $team = $this->createTeam($admin, 'GOALI TRANSPORTES');
        $team->users()->attach($admin->id, ['role' => 'Admin']);
        $admin->forceFill(['current_team_id' => $team->id])->save();
        $otherUser = $this->createUser('other@example.com');

        $this->actingAs($admin)
            ->from(route('teams.show', $team))
            ->post(route('team-members.store', $team), ['user_id' => $otherUser->id])
            ->assertRedirect(route('teams.show', $team))
            ->assertSessionHasErrorsIn('addTeamMember', 'user_id');

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $otherUser->id,
        ]);
    }

    private function createUser(string $email): User
    {
        $user = User::create([
            'name' => 'Usuário de teste',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

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
