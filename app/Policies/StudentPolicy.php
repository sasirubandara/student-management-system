<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can view the student listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view a student profile.
     */
    public function view(User $user, Student $student): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can register students.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update a student.
     */
    public function update(User $user, Student $student): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can archive a student.
     */
    public function delete(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore a student.
     */
    public function restore(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view archived students.
     */
    public function viewArchived(User $user): bool
    {
        return $user->isAdmin();
    }
}