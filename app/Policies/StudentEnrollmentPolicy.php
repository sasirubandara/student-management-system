<?php

namespace App\Policies;

use App\Models\StudentEnrollment;
use App\Models\User;

class StudentEnrollmentPolicy
{
    /**
     * Determine whether the user can view the enrollment listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view an enrollment.
     */
    public function view(User $user, StudentEnrollment $studentEnrollment): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create enrollments.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update an enrollment.
     */
    public function update(User $user, StudentEnrollment $studentEnrollment): bool
    {
        return $user->isAdmin();
    }
}