<?php

namespace App\Policies;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function view(User $user, Question $question): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Question $question): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Only an untouched draft can be removed outright. Anything that has been
     * published may already sit in an exam, and archiving keeps the record.
     */
    public function delete(User $user, Question $question): bool
    {
        return $this->viewAny($user) && $question->status === QuestionStatus::Draft;
    }

    public function changeStatus(User $user, Question $question): bool
    {
        return $this->viewAny($user);
    }
}
