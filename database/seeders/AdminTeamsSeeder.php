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
        $user = User::where('email', 'admin@hotmail.com')->first();

        $teams = [
            'SAC',
            'RMA',
            'TRIAGEM'
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
    }
}
