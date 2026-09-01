<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    /** Teachers organise their own chapters, so they manage topics too. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function view(User $user, Topic $topic): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Topic $topic): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $this->viewAny($user) && $topic->questions()->doesntExist();
    }
}
