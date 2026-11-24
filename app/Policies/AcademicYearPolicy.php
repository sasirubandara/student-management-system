<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    /**
     * Determine whether the user can view academic years.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine whether the user can view an academic year.
     */
    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create academic years.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine whether the user can update an academic year.
     */
    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $this->create($user);
    }

    /**
     * Academic years cannot be deleted through this module.
     */
    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return false;
    }
}
