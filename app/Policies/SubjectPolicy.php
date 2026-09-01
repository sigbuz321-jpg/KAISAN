<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function view(User $user, Subject $subject): bool
    {
        return $this->viewAny($user);
    }

    /** Subjects are the shape of the curriculum, so the owner sets them. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->isAdmin();
    }

    /** Deleting a subject would take its questions with it. */
    public function delete(User $user, Subject $subject): bool
    {
        return $user->isAdmin() && $subject->questions()->doesntExist();
    }
}
