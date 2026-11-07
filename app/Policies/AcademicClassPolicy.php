<?php

namespace App\Policies;

use App\Models\AcademicClass;
use App\Models\User;

class AcademicClassPolicy
{
    /**
     * Determine whether the user can view the class listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view a class.
     */
    public function view(User $user, AcademicClass $academicClass): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create classes.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update a class.
     */
    public function update(User $user, AcademicClass $academicClass): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete a class.
     */
    public function delete(User $user, AcademicClass $academicClass): bool
    {
        return $user->isAdmin();
    }
}