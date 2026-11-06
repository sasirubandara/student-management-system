<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'app:create-admin';

    /**
     * The command description.
     */
    protected $description = 'Create an administrator account';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $name = trim((string) $this->ask('Administrator name'));

        $email = strtolower(
            trim((string) $this->ask('Administrator email'))
        );

        // Password input is hidden in the terminal.
        $password = $this->secret('Administrator password');

        $confirmation = $this->secret('Confirm password');

        // Validate account information.
        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],

                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],

                'password' => [
                    'required',
                    'string',
                    Password::min(12)
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // Confirm the password before creating the account.
        if (
            ! is_string($password) ||
            ! is_string($confirmation) ||
            ! hash_equals($password, $confirmation)
        ) {
            $this->error('Password confirmation does not match.');

            return self::FAILURE;
        }

        // The User model automatically hashes the password.
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->info('Administrator account created successfully!');
        $this->line('Account email: ' . $user->email);

        return self::SUCCESS;
    }
}