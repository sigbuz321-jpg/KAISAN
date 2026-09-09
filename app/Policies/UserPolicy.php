<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Account management belongs to admin for all roles, and teachers can manage
     * student accounts (setting school level, grade, and classroom).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function view(User $user, User $subject): bool
    {
        if ($user->isAdmin() || $user->is($subject)) {
            return true;
        }

        return $user->isGuru() && $subject->isMurid();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function update(User $user, User $subject): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isGuru() && $subject->isMurid();
    }

    /**
     * Nobody deletes accounts. Rule 7 of domain-kaisan.md: a deactivated
     * student must stay attached to past exam attempts, so the UI offers
     * deactivation instead.
     */
    public function delete(User $user, User $subject): bool
    {
        return false;
    }

    public function deactivate(User $user, User $subject): bool
    {
        if ($user->isAdmin() && ! $user->is($subject)) {
            return true;
        }

        return $user->isGuru() && $subject->isMurid();
    }
}
