<?php

namespace App\Actions\Fortify;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Support\Jetstream;
use Illuminate\Support\Str;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        $this->validateInput($input);

        return DB::transaction(function () use ($input) {
            return tap($this->createUser($input), function (User $user) {
                $this->assignDefaultTeam($user);
            });
        });
    }

    protected function validateInput(array $input): void
    {
        Validator::make($input, $this->rules(), $this->messages())->validate();
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!Team::where('name', 'EQUIPE NÃO ATRIBUÍDA')->exists()) {
                        $fail('Lembre-se de rodar os seeders');
                    }
                },
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]/',
            ],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser válido.',
            'email.unique' => 'Este e-mail já foi registrado.',
            'email.regex' => 'O endereço de e-mail não é válido',
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ser uma string.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'terms.accepted' => 'Você deve aceitar os termos e a política de privacidade.',
            'terms.required' => 'A aceitação dos termos e da política de privacidade é obrigatória.',
        ];
    }

    protected function createUser(array $input): User
    {

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => Str::upper($input['name']),
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]), function (User $user) {
                $user->markEmailAsVerified();
            });
        });
    }

    protected function assignDefaultTeam(User $user): void
    {
        $team = Team::where('name', 'EQUIPE NÃO ATRIBUÍDA')->first();

        if ($team) {
            $user->teams()->attach($team->id, ['role' => 'Espectador']);
            $user->current_team_id = $team->id;
            $user->save();
        }
    }
}
