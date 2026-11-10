<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\User;

class AttendanceSessionPolicy
{
    /**
     * Determine whether the user can view any attendance sessions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view an attendance session.
     */
    public function view(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create attendance sessions.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update attendance sessions.
     */
    public function update(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete attendance sessions.
     */
    public function delete(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->isAdmin();
    }
}