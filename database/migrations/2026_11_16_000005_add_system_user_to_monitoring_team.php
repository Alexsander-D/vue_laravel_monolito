<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $teamId = DB::table('teams')
            ->where('name', 'MONITORAMENTO DO SISTEMA')
            ->value('id');

        if (! $teamId || ! DB::table('users')->where('id', 1)->exists()) {
            return;
        }

        $now = now();
        DB::table('team_user')->insertOrIgnore([
            'team_id' => $teamId,
            'user_id' => 1,
            'role' => 'Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionId = DB::table('permissions')->where([
            'team_id' => $teamId,
            'name' => 'visualizar-monitoramento',
        ])->value('id');
        $roleId = DB::table('roles')->where([
            'team_id' => $teamId,
            'name' => 'Admin',
        ])->value('id');

        if ($permissionId && $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $teamId = DB::table('teams')
            ->where('name', 'MONITORAMENTO DO SISTEMA')
            ->value('id');

        if ($teamId) {
            DB::table('team_user')
                ->where('team_id', $teamId)
                ->where('user_id', 1)
                ->delete();
        }
    }
};
