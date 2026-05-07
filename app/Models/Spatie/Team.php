<?php

namespace App\Models\Spatie;

use App\Models\Spatie\Role;
use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use App\Models\Spatie\Permission;
use App\Models\Spatie\TeamInvitation;

class Team extends JetstreamTeam
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'personal_team' => 'boolean',
    ];

    /**
     * Relationship: The user that owns the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: Users associated with the team.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Relationship: Roles associated with the team.
     */
    public function roles()
    {
        return $this->hasMany(Role::class, 'team_id');
    }

    /**
     * Relationship: Permissions associated with the team.
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'team_id');
    }

    /**
     * Get all users belonging to the team.
     */
    public function giveUsersByTeam()
    {
        return $this->users;
    }

    /**
     * Relationship: Team invitations associated with the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id');
    }
}
