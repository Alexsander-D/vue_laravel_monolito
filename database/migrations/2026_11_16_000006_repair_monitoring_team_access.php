<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const TEAM_NAME = 'MONITORAMENTO DO SISTEMA';
    private const ROLE_NAME = 'Admin';
    private const PERMISSION_NAME = 'visualizar-monitoramento';

    public function up(): void
    {
        $ownerId = DB::table('users')->where('id', 1)->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if (! $ownerId) {
            return;
        }

        $now = now();
        $teamId = DB::table('teams')->where('name', self::TEAM_NAME)->value('id');

        if (! $teamId) {
            $teamId = DB::table('teams')->insertGetId([
                'user_id' => $ownerId,
                'name' => self::TEAM_NAME,
                'personal_team' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('team_user')->insertOrIgnore([
            'team_id' => $teamId,
            'user_id' => $ownerId,
            'role' => self::ROLE_NAME,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $ownerId)
            ->update(['role' => self::ROLE_NAME, 'updated_at' => $now]);

        $guardName = config('auth.defaults.guard', 'web');
        $permissionId = DB::table('permissions')->where([
            'team_id' => $teamId,
            'name' => self::PERMISSION_NAME,
            'guard_name' => $guardName,
        ])->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'team_id' => $teamId,
                'name' => self::PERMISSION_NAME,
                'guard_name' => $guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleId = DB::table('roles')->where([
            'team_id' => $teamId,
            'name' => self::ROLE_NAME,
            'guard_name' => $guardName,
        ])->value('id');

        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'team_id' => $teamId,
                'name' => self::ROLE_NAME,
                'guard_name' => $guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('role_has_permissions')->insertOrIgnore([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // The repair is intentionally non-destructive. The original monitoring
        // team migration owns the team and its initial records.
    }
};
