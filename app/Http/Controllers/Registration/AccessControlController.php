<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Actions\Jetstream\AddTeamMember;
use App\Models\Spatie\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Support\Jetstream;

/**
 * Manipula o controle de acesso para usuários e times.
 */
class AccessControlController extends Controller
{
    /**
     * Exibe a página de controle de acesso.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();

        $unassignedTeam = Team::whereName('EQUIPE NÃO ATRIBUÍDA')->with('users')->first();
        $users = $unassignedTeam ? $unassignedTeam->users : collect();

        $adminTeams = Team::wherePersonalTeam($user->id)->with('users')->get();

        return Inertia::render('Registration/AccessControl/Show', [
            'unassignedUsers' => $users,
            'ownTeams' => $adminTeams,
            'availableRoles' => array_values(Jetstream::$roles),
            'currentUser' => $user,
        ]);
    }

    /**
     * Adiciona usuário a equipe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'team_id.value' => 'required|exists:teams,id',
            'email' => 'required|email|exists:users,email',
            'role' => 'required|string',
        ], [
            'team_id.value.required' => 'SELECIONE UM TIME',
            'team_id.value.exists' => 'TIME NÃO ENCONTRADO',
            'email.required' => 'INFORME UM E-MAIL VÁLIDO',
            'email.email' => 'INFORME UM E-MAIL VÁLIDO',
            'email.exists' => 'USUÁRIO NÃO ENCONTRADO',
            'role.required' => 'SELECIONE UM ROLE',
            'role.string' => 'INFORME UM ROLE VÁLIDO',
        ]);

        $team = Team::findOrFail($request->team_id['value']);

        app(AddTeamMember::class)->add($user, $team, $request->email, $request->role);

        return redirect()->back()->with('message', 'MEMBRO ADICIONADO COM SUCESSO!');
    }

    /**
     * Obtém roles para equipe.
     *
     * @param  \App\Models\Spatie\Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Team $team)
    {
        $team->load('owner', 'users', 'teamInvitations');
        $roles = $team->roles()->select('id', 'name')->get();

        return response()->json($roles);
    }
}
