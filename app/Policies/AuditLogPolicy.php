<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the user can view the audit log directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view an individual audit record.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->isAdmin();
    }
}