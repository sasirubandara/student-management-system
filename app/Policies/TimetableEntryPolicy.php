<?php

namespace App\Policies;

use App\Models\TimetableEntry;
use App\Models\User;

class TimetableEntryPolicy
{
    /**
     * Active administrators and staff can view timetables.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Active administrators and staff can view an entry.
     */
    public function view(
        User $user,
        TimetableEntry $timetableEntry
    ): bool {
        return $this->viewAny($user);
    }

    /**
     * Only active administrators can create entries.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Only active administrators can update entries.
     */
    public function update(
        User $user,
        TimetableEntry $timetableEntry
    ): bool {
        return $this->create($user);
    }

    /**
     * Only active administrators can delete entries.
     */
    public function delete(
        User $user,
        TimetableEntry $timetableEntry
    ): bool {
        return $this->create($user);
    }
}
