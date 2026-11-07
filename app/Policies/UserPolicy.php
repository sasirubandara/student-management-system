<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateRole(User $user, User $targetUser): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, User $targetUser): bool
    {
        return $user->isAdmin();
    }
}