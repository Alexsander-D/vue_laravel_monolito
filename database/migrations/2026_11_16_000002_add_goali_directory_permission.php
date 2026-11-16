<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $teamId = DB::table('teams')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['goali transportes'])
            ->value('id');

        if (! $teamId) {
            return;
        }

        $guardName = config('auth.defaults.guard', 'web');
        $permissionName = 'goali-gerenciar-cadastros';
        $permissionId = DB::table('permissions')->where([
            'team_id' => $teamId,
            'name' => $permissionName,
            'guard_name' => $guardName,
        ])->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'team_id' => $teamId,
                'name' => $permissionName,
                'guard_name' => $guardName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table('roles')
            ->where('team_id', $teamId)
            ->whereIn('name', ['Admin', 'Administrativo'])
            ->where('guard_name', $guardName)
            ->pluck('id');

        foreach ($roleIds as $roleId) {
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
            ->whereRaw('LOWER(TRIM(name)) = ?', ['goali transportes'])
            ->value('id');

        if (! $teamId) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('team_id', $teamId)
            ->where('name', 'goali-gerenciar-cadastros')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
