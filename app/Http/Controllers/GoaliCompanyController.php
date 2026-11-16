<?php

namespace App\Http\Controllers;

use App\Models\GoaliCompany;
use App\Models\Spatie\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GoaliCompanyController extends Controller
{
    private const PERMISSION_MANAGE_DIRECTORY = 'goali-gerenciar-cadastros';

    public function index(Request $request)
    {
        $user = $this->authorizeDirectory($request);

        return Inertia::render('Goali/Companies', [
            'companies' => GoaliCompany::query()
                ->where('team_id', $user->current_team_id)
                ->withCount('passengers')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->authorizeDirectory($request);
        $validated = $request->validate($this->rules($user));
        GoaliCompany::create([...$validated, 'team_id' => $user->current_team_id]);

        return back()->with('success', 'Empresa cadastrada com sucesso.');
    }

    public function update(Request $request, GoaliCompany $company)
    {
        $user = $this->authorizeDirectory($request);
        $company = $this->companyForUser($user, $company);
        $company->update($request->validate($this->rules($user, $company)));

        return back()->with('success', 'Empresa atualizada com sucesso.');
    }

    public function destroy(Request $request, GoaliCompany $company)
    {
        $user = $this->authorizeDirectory($request);
        $this->companyForUser($user, $company)->delete();

        return back()->with('success', 'Empresa excluída com sucesso.');
    }

    private function rules(User $user, ?GoaliCompany $company = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('goali_companies', 'name')
                    ->where(fn ($query) => $query->where('team_id', $user->current_team_id))
                    ->ignore($company?->id),
            ],
            'departure_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    private function companyForUser(User $user, GoaliCompany $company): GoaliCompany
    {
        abort_unless((int) $company->team_id === (int) $user->current_team_id, 404);

        return $company;
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
