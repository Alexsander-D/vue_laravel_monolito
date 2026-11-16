<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;

class AdminTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', env('MAIL_USERNAME'))->first() ?? User::find(1);
        
        $teams = [
            'SAC',
            'RMA',
            'TRIAGEM',
            'BARBEARIA CARIOCA 2'
        ];

        foreach ($teams as $teamName) {
            $team = Team::create([
                'name' => $teamName,
                'personal_team' => true,
                'user_id' => $user->id,
            ]);
            $user->teams()->attach($team->id, ['role' => 'Admin']);

            $roles = [
                'Admin',
                'Espectador',
                'Monitor',
                'Auxiliar Administrativo',
                'Analista de dados',
                'Auxiliar Tecnico',
                'Apoio'
            ];

            $permissions = [];

            if ($teamName === 'TRIAGEM') {
                $roles = array_merge($roles, ['Lider', 'Atendimento']);
                $permissions = array_merge($permissions, [
                    'gerir-equipe',
                    'gerir-permissoes',
                    'cadastrar-falhas',
                    'cadastrar-produtos',
                    'gerir-laudos',
                    'gerir-materiais'
                ]);
            }

            if ($teamName === 'SAC') {
                $roles = array_merge($roles, ['Tecnico', 'Orcamento']);
                $permissions = array_merge($permissions, [
                    'separar-rastreio',
                    'coletar-rastreio',
                    'gerir-equipe',
                    'gerir-permissoes',
                    'realizar-entrada',
                    'atribuir-fila',
                    'realizar-laudo',
                    'visualizar-relatorio',
                    'visualizar-relatorio-individual',
                    'cadastrar-falhas',
                    'cadastrar-produtos'
                ]);
            }

            if ($teamName === 'RMA') {
                $roles = array_merge($roles, ['Embalagem']);
                $permissions = array_merge($permissions, [
                    'gerir-equipe',
                    'gerir-permissoes',
                    'realizar-entrada',
                    'atribuir-fila',
                    'realizar-laudo',
                    'visualizar-relatorio',
                    'visualizar-relatorio-individual',
                    'cadastrar-falhas',
                    'cadastrar-produtos',
                    'embalagem'
                ]);
            }

            if ($teamName === 'BARBEARIA CARIOCA 2') {
                $roles = [];
                $roles = array_merge($roles, ['Admin', 'Barbeiro', 'Consultor']);
                $permissions = array_merge($permissions, [
                    'gerir-equipe',
                    'gerir-permissoes',
                    'alterar-relatorios'
                ]);
            }

            foreach ($roles as $roleName) {
                $role =  Role::firstOrCreate([
                    'name' => $roleName,
                    'team_id' => $team->id,
                ]);

                foreach ($permissions as $permissionName) {
                    $permission = Permission::firstOrCreate(['name' => $permissionName, 'team_id' => $team->id]);
                    if ($roleName === 'Admin') {
                        $role->permissions()->attach($permission->id);
                    }
                }
            }
        }

        $monitoringTeam = Team::firstOrCreate(
            ['name' => 'MONITORAMENTO DO SISTEMA'],
            ['personal_team' => true, 'user_id' => $user->id]
        );
        $user->teams()->syncWithoutDetaching([$monitoringTeam->id => ['role' => 'Admin']]);

        $monitoringPermission = Permission::firstOrCreate([
            'name' => 'visualizar-monitoramento',
            'team_id' => $monitoringTeam->id,
        ]);
        $monitoringRole = Role::firstOrCreate([
            'name' => 'Admin',
            'team_id' => $monitoringTeam->id,
        ]);
        $monitoringRole->permissions()->syncWithoutDetaching([$monitoringPermission->id]);

        $systemUser = User::find(1);
        if ($systemUser) {
            $systemUser->teams()->syncWithoutDetaching([$monitoringTeam->id => ['role' => 'Admin']]);
        }
    }
}
