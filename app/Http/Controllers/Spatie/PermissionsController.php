<?php

namespace App\Http\Controllers\Spatie;

use App\Http\Controllers\Controller;
use App\Models\Spatie\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Spatie\Permission;

class PermissionsController extends Controller
{
    /**
     * Armazena uma nova permissão no banco de dados.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $validatedData = $this->validatePermissionData($request);

        $permission = Permission::firstOrCreate(
            ['name' => $validatedData['permissionInput'], 'team_id' => $teamId]
        );

        if ($permission) {
            return redirect()->route('roles.create')->with(
                'message',
                'PERMISSAO CRIADA COM SUCESSO!'
            );
        }

        return back()->withErrors([
            'permissionInput' => 'CONTATE O ADMINISTRADOR'
        ])->withInput();
    }

    /**
     * Armazena uma nova permissão em uma função.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePermissionRoleRelation(Request $request)
    {
        $user = Auth::user();

        $validatedData = $this->validatePermissionRoleData($request);

        $permission = Permission::findOrFail($validatedData["permissionSelect"]["id"]);
        $role = Role::findOrFail($validatedData['roleSelect']["id"]);

        if ($role->permissions->contains($permission)) {
            $role->permissions()->detach($permission->id);
        } else {
            $role->permissions()->attach($permission->id);
        }

        return redirect()->route('roles.create')->with([
            'message' => 'PERMISSAO ASSOCIADA COM SUCESSO!'
        ]);
    }

    /**
     * Valida os dados da permissão.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function validatePermissionData(Request $request)
    {
        $messages = [
            'permissionInput.required' => 'CAMPO NOME EH OBRIGATORIO.',
            'permissionInput.string' => 'NOME DEVE SER UMA STRING.',
            'permissionInput.max' => 'NOME NAO PODE TER MAIS DE 255 CARACTERES.',
            'permissionInput.regex' => 'NOME DEVE CONTER APENAS LETRAS MINUSCULAS, NUMEROS E HIFENS, SEM ESPACOS OU ACENTOS.',
        ];

        return $request->validate([
            'permissionInput' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z-]+$/',
            ],
        ], $messages);
    }

    /**
     * Valida os dados da permissão e da função.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function validatePermissionRoleData(Request $request)
    {
        $messages = [
            'permissionSelect.id.required' => 'CAMPO PERMISSAO EH OBRIGATORIO.',
            'permissionSelect.id.string' => 'CAMPO PERMISSAO DEVE SER UMA STRING.',
            'permissionSelect.id.exists' => 'PERMISSAO SELECIONADA NAO EXISTE.',
            'roleSelect.id.required' => 'VOCE DEVE SELECIONAR UMA FUNCAO.',
            'roleSelect.id.exists' => 'FUNCAO SELECIONADA NAO EXISTE.',
        ];

        return $request->validate([
            'permissionSelect.id' => [
                'required',
                'exists:permissions,id',
            ],
            'roleSelect.id' => 'required|exists:roles,id',
        ], $messages);
    }
}

