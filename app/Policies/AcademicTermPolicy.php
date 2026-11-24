<?php

namespace App\Policies;

use App\Models\AcademicTerm;
use App\Models\User;

class AcademicTermPolicy
{
    /**
     * Determine whether the user can view academic terms.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine whether the user can view an academic term.
     */
    public function view(User $user, AcademicTerm $academicTerm): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create academic terms.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine whether the user can update an academic term.
     */
    public function update(User $user, AcademicTerm $academicTerm): bool
    {
        return $this->create($user);
    }

    /**
     * Academic terms cannot be deleted through this module.
     */
    public function delete(User $user, AcademicTerm $academicTerm): bool
    {
        return false;
    }
}
