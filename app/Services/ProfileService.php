<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Update the authenticated user's personal information.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        User $user,
        array $attributes
    ): User {
        try {
            return DB::transaction(function () use (
                $user,
                $attributes
            ): User {
                $profile = User::query()
                    ->whereKey($user->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless($profile->isActive(), 403);

                $emailChanged =
                    $profile->email !== $attributes['email'];

                if ($emailChanged) {
                    $currentPassword =
                        $attributes['current_password'] ?? '';

                    if (! Hash::check(
                        $currentPassword,
                        $profile->password
                    )) {
                        throw ValidationException::withMessages([
                            'current_password' => 'The current password is incorrect.',
                        ]);
                    }
                }

                $profile->fill([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                ]);

                if ($emailChanged) {
                    $profile->forceFill([
                        'email_verified_at' => null,
                    ]);
                }

                if ($profile->isDirty()) {
                    $profile->save();
                }

                return $profile;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'email' => 'This email address is already associated with another account.',
            ]);
        }
    }

    /**
     * Change the authenticated user's password.
     */
    public function updatePassword(
        User $user,
        #[\SensitiveParameter] string $currentPassword,
        #[\SensitiveParameter] string $newPassword
    ): User {
        return DB::transaction(function () use (
            $user,
            $currentPassword,
            $newPassword
        ): User {
            $profile = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($profile->isActive(), 403);

            if (! Hash::check(
                $currentPassword,
                $profile->password
            )) {
                throw ValidationException::withMessages([
                    'current_password' => 'The current password is incorrect.',
                ])->errorBag('updatePassword');
            }

            if (Hash::check(
                $newPassword,
                $profile->password
            )) {
                throw ValidationException::withMessages([
                    'password' => 'Your new password must be different from your current password.',
                ])->errorBag('updatePassword');
            }

            $profile->password = $newPassword;

            $profile->setRememberToken(
                Str::random(60)
            );

            $profile->save();

            return $profile;
        }, 3);
    }
}
