<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $ownerId = DB::table('users')->where('id', 1)->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if (! $ownerId) {
            return;
        }

        $now = now();
        $teamId = DB::table('teams')->where('name', 'MONITORAMENTO DO SISTEMA')->value('id');

        if (! $teamId) {
            $teamId = DB::table('teams')->insertGetId([
                'user_id' => $ownerId,
                'name' => 'MONITORAMENTO DO SISTEMA',
                'personal_team' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('team_user')->insertOrIgnore([
            'team_id' => $teamId,
            'user_id' => $ownerId,
            'role' => 'Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $guardName = config('auth.defaults.guard', 'web');
        $permissionId = DB::table('permissions')->where([
            'team_id' => $teamId,
            'name' => 'visualizar-monitoramento',
            'guard_name' => $guardName,
        ])->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'team_id' => $teamId,
                'name' => 'visualizar-monitoramento',
                'guard_name' => $guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleId = DB::table('roles')->where([
            'team_id' => $teamId,
            'name' => 'Admin',
            'guard_name' => $guardName,
        ])->value('id');

        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'team_id' => $teamId,
                'name' => 'Admin',
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
        $teamId = DB::table('teams')->where('name', 'MONITORAMENTO DO SISTEMA')->value('id');

        if ($teamId) {
            DB::table('teams')->where('id', $teamId)->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
