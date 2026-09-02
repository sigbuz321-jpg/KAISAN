<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    /**
     * A student reads their own attempt and nobody else's.
     *
     * .claude/rules/security.md names this specifically and asks for a test
     * rather than a manual check, because an attempt row carries another
     * child's marks.
     */
    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGuru()) {
            return $attempt->exam->created_by === $user->id;
        }

        return $attempt->user_id === $user->id;
    }

    /** Only the owner works on an attempt -- never a teacher on their behalf. */
    public function update(User $user, ExamAttempt $attempt): bool
    {
        return $user->isMurid()
            && $attempt->user_id === $user->id
            && ! $attempt->isSubmitted()
            && ! $attempt->isVoided();
    }

    public function submit(User $user, ExamAttempt $attempt): bool
    {
        return $this->update($user, $attempt);
    }

    /**
     * Rule 6: a result is never deleted. Voiding keeps the row and the score
     * and records why, so the bimbel can explain it to a parent later.
     */
    public function void(User $user, ExamAttempt $attempt): bool
    {
        return $user->isAdmin() || ($user->isGuru() && $attempt->exam->created_by === $user->id);
    }

    public function delete(User $user, ExamAttempt $attempt): bool
    {
        return false;
    }
}
