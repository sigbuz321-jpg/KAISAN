<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Account management belongs to the admin alone in M1.
     * Teachers get scoped access to their own students once teaching
     * assignments exist -- there is no such relation in the schema yet.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $subject): bool
    {
        return $user->isAdmin() || $user->is($subject);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $subject): bool
    {
        return $user->isAdmin();
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
        // An admin locking themselves out cannot be undone from the panel.
        return $user->isAdmin() && ! $user->is($subject);
    }
}
