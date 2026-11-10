<?php

namespace App\Policies;

use App\Models\ExaminationResult;
use App\Models\User;

class ExaminationResultPolicy
{
    /**
     * Determine whether the user can view any examination results.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view an examination result.
     */
    public function view(
        User $user,
        ExaminationResult $examinationResult
    ): bool {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create examination results.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update an examination result.
     */
    public function update(
        User $user,
        ExaminationResult $examinationResult
    ): bool {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete an examination result.
     */
    public function delete(
        User $user,
        ExaminationResult $examinationResult
    ): bool {
        return $user->isAdmin();
    }
}