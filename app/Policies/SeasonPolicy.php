<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;

class SeasonPolicy
{
    /** Teachers see which season is running; only the owner changes it. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function view(User $user, Season $season): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Resetting a season is the owner's decision, not a teacher's: it ends the
     * competition everyone has been working towards.
     */
    public function reset(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Season $season): bool
    {
        return false;
    }

    /** Seasons are history. Ending one is the only way to close it. */
    public function delete(User $user, Season $season): bool
    {
        return false;
    }
}
