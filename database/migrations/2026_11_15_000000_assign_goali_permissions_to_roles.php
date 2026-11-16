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
        'Admin' => [
            'goali-visualizar-corridas',
            'goali-visualizar-todas-corridas',
            'goali-criar-corridas',
            'goali-criar-para-motoristas',
            'goali-gerenciar-corridas',
            'goali-cancelar-corridas',
            'goali-excluir-corridas',
        ],
        'Administrativo' => [
            'goali-visualizar-corridas',
            'goali-criar-corridas',
            'goali-criar-para-motoristas',
        ],
    ];

    public function up(): void
    {
        $teamId = $this->goaliTeamId();
        if (! $teamId) {
            return;
        }

        $guardName = config('auth.defaults.guard', 'web');
        $permissionIds = [];

        foreach (array_unique(array_merge(...array_values(self::PERMISSIONS_BY_ROLE))) as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('team_id', $teamId)
                ->where('name', $permissionName)
                ->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'guard_name' => $guardName,
                    'team_id' => $teamId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $permissionIds[$permissionName] = $permissionId;
        }

        foreach (self::PERMISSIONS_BY_ROLE as $roleName => $rolePermissions) {
            $roleId = DB::table('roles')
                ->where('team_id', $teamId)
                ->where('name', $roleName)
                ->where('guard_name', $guardName)
                ->value('id');

            if (! $roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $roleName,
                    'guard_name' => $guardName,
                    'team_id' => $teamId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($rolePermissions as $permissionName) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$permissionName],
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $teamId = $this->goaliTeamId();
        if (! $teamId) {
            return;
        }

        $permissionNames = array_unique(array_merge(...array_values(self::PERMISSIONS_BY_ROLE)));
        $permissionIds = DB::table('permissions')
            ->where('team_id', $teamId)
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')
            ->where('team_id', $teamId)
            ->whereIn('name', $permissionNames)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function goaliTeamId(): ?int
    {
        $teamId = DB::table('teams')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['goali transportes'])
            ->value('id');

        return $teamId ? (int) $teamId : null;
    }
};
