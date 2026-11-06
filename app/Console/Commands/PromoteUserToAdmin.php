<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class PromoteUserToAdmin extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'app:promote-admin';

    /**
     * The command description.
     */
    protected $description = 'Promote an existing user to administrator';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $email = strtolower(
            trim((string) $this->ask('Enter the account email to promote'))
        );

        $validator = Validator::make(
            ['email' => $email],
            [
                'email' => ['required', 'email', 'max:255'],
            ]
        );

        if ($validator->fails()) {
            $this->error('Please enter a valid email address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error('No user account was found.');

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->info('This account is already an administrator.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Current Role'],
            [[
                $user->id,
                $user->name,
                $user->role,
            ]]
        );

        if (! $this->confirm(
            'Are you sure you want to promote this account to administrator?'
        )) {
            $this->warn('Promotion cancelled.');

            return self::SUCCESS;
        }

        $user->role = User::ROLE_ADMIN;
        $user->save();

        $this->info('Administrator role assigned successfully!');

        return self::SUCCESS;
    }
}