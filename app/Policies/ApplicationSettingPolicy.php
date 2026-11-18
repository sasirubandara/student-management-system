<?php

namespace App\Policies;

use App\Models\User;

class ApplicationSettingPolicy
{
    /**
     * Determine whether the user can view application settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    /**
     * Determine whether the user can manage application settings.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }
}
