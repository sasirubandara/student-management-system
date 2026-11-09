<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    /**
     * Determine whether the user can view the teacher directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view a teacher profile.
     */
    public function view(User $user, Teacher $teacher): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can register teachers.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update teachers.
     */
    public function update(User $user, Teacher $teacher): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can archive teachers.
     */
    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore archived teachers.
     */
    public function restore(User $user, Teacher $teacher): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view archived teachers.
     */
    public function viewArchived(User $user): bool
    {
        return $user->isAdmin();
    }
}