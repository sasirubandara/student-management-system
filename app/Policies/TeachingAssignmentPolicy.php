<?php

namespace App\Policies;

use App\Models\TeachingAssignment;
use App\Models\User;

class TeachingAssignmentPolicy
{
    /**
     * Determine whether the user can view teaching assignments.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view an individual assignment.
     */
    public function view(
        User $user,
        TeachingAssignment $teachingAssignment
    ): bool {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create teaching assignments.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update teaching assignments.
     */
    public function update(
        User $user,
        TeachingAssignment $teachingAssignment
    ): bool {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can remove teaching assignments.
     */
    public function delete(
        User $user,
        TeachingAssignment $teachingAssignment
    ): bool {
        return $user->isAdmin();
    }
}