<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    /**
     * Determine whether the user can view the subject listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view a subject.
     */
    public function view(User $user, Subject $subject): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create subjects.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update a subject.
     */
    public function update(User $user, Subject $subject): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete a subject.
     */
    public function delete(User $user, Subject $subject): bool
    {
        return $user->isAdmin();
    }
}