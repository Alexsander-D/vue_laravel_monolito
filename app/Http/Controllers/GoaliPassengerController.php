<?php

namespace App\Http\Controllers;

use App\Models\GoaliCompany;
use App\Models\GoaliPassenger;
use App\Models\Spatie\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GoaliPassengerController extends Controller
{
    private const PERMISSION_MANAGE_DIRECTORY = 'goali-gerenciar-cadastros';

    public function index(Request $request)
    {
        $user = $this->authorizeDirectory($request);

        return Inertia::render('Goali/Passengers', [
            'passengers' => GoaliPassenger::query()
                ->where('team_id', $user->current_team_id)
                ->with('company:id,name')
                ->orderBy('name')
                ->get(),
            'companies' => GoaliCompany::query()
                ->where('team_id', $user->current_team_id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->authorizeDirectory($request);
        $validated = $request->validate($this->rules($user));
        GoaliPassenger::create([...$validated, 'team_id' => $user->current_team_id]);

        return back()->with('success', 'Passageiro cadastrado com sucesso.');
    }

    public function update(Request $request, GoaliPassenger $passenger)
    {
        $user = $this->authorizeDirectory($request);
        $passenger = $this->passengerForUser($user, $passenger);
        $passenger->update($request->validate($this->rules($user)));

        return back()->with('success', 'Passageiro atualizado com sucesso.');
    }

    public function destroy(Request $request, GoaliPassenger $passenger)
    {
        $user = $this->authorizeDirectory($request);
        $this->passengerForUser($user, $passenger)->delete();

        return back()->with('success', 'Passageiro excluído com sucesso.');
    }

    private function rules(User $user): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists('goali_companies', 'id')->where(fn ($query) => $query->where('team_id', $user->current_team_id)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'boarding_point' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    private function passengerForUser(User $user, GoaliPassenger $passenger): GoaliPassenger
    {
        abort_unless((int) $passenger->team_id === (int) $user->current_team_id, 404);

        return $passenger;
    }

    private function authorizeDirectory(Request $request): User
    {
        $user = $request->user();
        abort_unless($this->hasPermission($user, self::PERMISSION_MANAGE_DIRECTORY), 403);

        return $user;
    }

    private function hasPermission(?User $user, string $permission): bool
    {
        return $user && collect($user->getPermissionsInTeamByUser())->contains($permission);
    }
}
