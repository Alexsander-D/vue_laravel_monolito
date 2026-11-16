<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS_BY_ROLE = [
        'Motorista' => [
            'goali-visualizar-corridas',
            'goali-criar-corridas',
            'goali-atualizar-status',
            'goali-cancelar-corridas',
        ],
        'Administrativo' => [
            'goali-visualizar-corridas',
            'goali-criar-corridas',
            'goali-criar-para-motoristas',
            'goali-cancelar-corridas',
        ],
        'Admin' => [
            'goali-visualizar-corridas',
            'goali-criar-corridas',
            'goali-cancelar-corridas',
            'goali-visualizar-todas-corridas',
            'goali-criar-para-motoristas',
            'goali-gerenciar-corridas',
            'goali-excluir-corridas',
        ],
    ];

    public function up(): void
    {
        $teamId = DB::table('teams')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['goali transportes'])
            ->value('id');

        if (! $teamId) {
            return;
        }

        $guardName = config('auth.defaults.guard', 'web');

        foreach (self::PERMISSIONS_BY_ROLE as $roleName => $permissionNames) {
            $roleId = DB::table('roles')->where([
                'team_id' => $teamId,
                'name' => $roleName,
                'guard_name' => $guardName,
            ])->value('id');

            if (! $roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'team_id' => $teamId,
                    'name' => $roleName,
                    'guard_name' => $guardName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($permissionNames as $permissionName) {
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

                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
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

        $permissionNames = array_values(array_unique(array_merge(...array_values(self::PERMISSIONS_BY_ROLE))));
        $permissionIds = DB::table('permissions')
            ->where('team_id', $teamId)
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
