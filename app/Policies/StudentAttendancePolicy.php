<?php

namespace App\Policies;

use App\Models\StudentAttendance;
use App\Models\User;

class StudentAttendancePolicy
{
    /**
     * Determine whether the user can view any attendance records.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view an attendance record.
     */
    public function view(User $user, StudentAttendance $studentAttendance): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update attendance records.
     */
    public function update(User $user, StudentAttendance $studentAttendance): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete attendance records.
     */
    public function delete(User $user, StudentAttendance $studentAttendance): bool
    {
        return $user->isAdmin();
    }
}