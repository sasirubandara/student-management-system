<?php

namespace App\Policies;

use App\Models\StudentPromotion;
use App\Models\User;

class StudentPromotionPolicy
{
    /**
     * View promotion history.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * View an individual promotion.
     */
    public function view(
        User $user,
        StudentPromotion $studentPromotion
    ): bool {
        return $this->viewAny($user);
    }

    /**
     * Create a new student promotion.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
