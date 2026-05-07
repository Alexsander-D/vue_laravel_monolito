<?php

namespace App\Models\Spatie;

use App\Models\External\Screening;
use App\Models\Spatie\Team;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Spatie\Permission;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * Os atributos que s o massivamente atribu veis.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Os atributos que devem ser ocultos para serializa-lo.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Os acessores a serem anexados à forma de array do modelo.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Enviar a notificação de verificação de e-mail personalizada.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        try {
            $this->notify(new CustomVerifyEmail());
        } catch (\Exception $e) {
            Log::error('Erro ao enviar o e-mail: ' . $e->getMessage());
        }
    }

    /**
     * Obtem os atributos que devem ser convertidos para um tipo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Retorna todas as triagens do usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function screenings()
    {
        return $this->hasMany(Screening::class);
    }

    /**
     * Retorna todas as permisscoes do usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * D  permissao ao usuario.
     *
     * Se a permissao nao existir, ser  criada.
     *
     * @param  string  $permission
     * @return void
     */
    public function givePermissionTo(string $permission): void
    {
        $permission = Permission::query()->firstOrCreate(['name' => $permission]);

        $this->permissions()->attach($permission);
    }

    /**
     * Verifica se o usuario tem uma determinada funcao em uma equipe.
     *
     * @param  int  $teamId
     * @param  string  $roleName
     * @return bool
     */
    public function hasRoleInTeam($teamId, $roleName)
    {
        return DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $this->id)
            ->where('role', $roleName)
            ->exists();
    }

    /**
     * Retorna todas as equipes que o usuario faz parte.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Retorna a funcao do usuario em sua equipe atual.
     *
     * @return string|null
     */
    public function getRoleByUser()
    {
        if (!$this->current_team_id) {
            return null;
        }

        return $this->teams()
            ->wherePivot('team_id', $this->current_team_id)
            ->wherePivot('user_id', $this->id)
            ->pluck('team_user.role')
            ->first() ?? null;
    }

    /**
     * Retorna todas as permiss es do usuario em sua equipe atual.
     *
     * Apenas retorna as permiss es se o usuario tiver uma equipe atual.
     *
     * @return array<string>
     */
    public function getPermissionsInTeamByUser()
    {
        $role = $this->getRoleByUser();

        if (!$role) {
            return [];
        }

        $roleInfo = Role::where('name', $role)
            ->where('team_id', $this->current_team_id)
            ->first();

        return $roleInfo ? $roleInfo->permissions()->pluck('name') : [];
    }
}
