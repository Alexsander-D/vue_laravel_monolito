<?php

namespace App\Actions\Fortify;

use App\Models\Spatie\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Throwable;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        if (! empty($input['photo'])) {
            try {
                $disk = Storage::disk(config('jetstream.profile_photo_disk', 'public'));
                $disk->makeDirectory('profile-photos');
                $oldPath = $user->profile_photo_path;
                $path = $input['photo']->storePublicly('profile-photos', [
                    'disk' => config('jetstream.profile_photo_disk', 'public'),
                ]);

                if (! $path) {
                    throw new \RuntimeException('Não foi possível gravar a foto do perfil.');
                }

                $user->forceFill(['profile_photo_path' => $path])->save();

                if ($oldPath && $oldPath !== $path) {
                    $disk->delete($oldPath);
                }
            } catch (Throwable $exception) {
                report($exception);

                throw ValidationException::withMessages([
                    'photo' => 'Não foi possível salvar a foto. Verifique as permissões do diretório de armazenamento.',
                ])->errorBag('updateProfileInformation');
            }
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
