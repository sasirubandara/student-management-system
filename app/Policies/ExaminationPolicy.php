<?php

namespace App\Policies;

use App\Models\Examination;
use App\Models\User;

class ExaminationPolicy
{
    /**
     * Determine whether the user can view any examinations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view an examination.
     */
    public function view(User $user, Examination $examination): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create examinations.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update an examination.
     */
    public function update(User $user, Examination $examination): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete an examination.
     */
    public function delete(User $user, Examination $examination): bool
    {
        return $user->isAdmin();
    }
}