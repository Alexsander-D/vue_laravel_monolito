<?php

namespace App\Http\Controllers\Spatie;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Spatie\Role;
use Inertia\Inertia;
use App\Models\Spatie\Permission;
use Illuminate\Support\Facades\Auth;

class RolesController extends Controller
{
    /**
     * Retorna todas as permissões de um time.
     *
     * @param int $teamId
     * @return array
     */
    public function getPermissions($teamId): array
    {
        return Permission::whereTeamId($teamId)->get()->toArray();
    }

    /**
     * Renderiza a página de funções.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        $rolesWithPermissions = Role::whereTeamId($team->id)
            ->get()
            ->map(function ($role) {
                return [
                    'role' => $role,
                    'permissions' => Permission::whereIn('id', function ($query) use ($role) {
                        $query->select('permission_id')
                            ->from('role_has_permissions')
                            ->where('role_id', $role->id);
                    })->get(),
                ];
            })
            ->toArray();

        $permissions = $this->getPermissions($team->id);

        return Inertia::render('Spatie/Roles', [
            'rolesWithPermissions' => $rolesWithPermissions,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Salva uma nova função em storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $request->validate([
            'roleInput' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z][a-zA-Z-]*$/',
            ],
        ], [
            'roleInput.required' => 'O CAMPO NOME E OBRIGATORIO.',
            'roleInput.string' => 'O NOME DEVE SER UMA STRING.',
            'roleInput.max' => 'O NOME NAO PODE TER MAIS DE 20 CARACTERES.',
            'roleInput.regex' => 'O NOME DEVE COMECAR COM UMA LETRA MAIUSCULA, NAO CONTER ESPACOS, NUMEROS E CARACTERES ESPECIAIS.',
        ]);

        if (Role::whereName($request->roleInput)->whereTeamId($teamId)->exists()) {
            return back()->withErrors(['roleInput' => 'ESSA FUNCAO JA EXISTE NO TIME.']);
        }

        Role::create([
            'name' => $request->roleInput,
            'team_id' => $teamId,
        ]);

        return redirect()->route('roles.create')->with('success', 'FUNCAO ASSOCIADA COM SUCESSO!');
    }
}
