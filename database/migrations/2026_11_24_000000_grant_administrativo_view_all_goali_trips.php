<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guardName = config('auth.defaults.guard', 'web');
        $teamIds = DB::table('teams')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['goali transportes'])
            ->pluck('id');

        foreach ($teamIds as $teamId) {
            $roleId = DB::table('roles')->where([
                'team_id' => $teamId,
                'name' => 'Administrativo',
                'guard_name' => $guardName,
            ])->value('id');

            if (! $roleId) {
                continue;
            }

            $permissionId = DB::table('permissions')->where([
                'team_id' => $teamId,
                'name' => 'goali-visualizar-todas-corridas',
                'guard_name' => $guardName,
            ])->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'team_id' => $teamId,
                    'name' => 'goali-visualizar-todas-corridas',
                    'guard_name' => $guardName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $teamIds = DB::table('teams')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['goali transportes'])
            ->pluck('id');
        $roleIds = DB::table('roles')
            ->whereIn('team_id', $teamIds)
            ->where('name', 'Administrativo')
            ->pluck('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('team_id', $teamIds)
            ->where('name', 'goali-visualizar-todas-corridas')
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
