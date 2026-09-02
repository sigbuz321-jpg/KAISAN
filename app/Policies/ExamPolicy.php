<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($user->isAdmin() || $user->isGuru()) {
            return true;
        }

        // A student may know an exam exists once it has left the teacher's
        // desk, but sitting it is decided by ExamAttemptPolicy.
        return $user->isMurid() && $exam->status->isVisibleToStudents();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    /** Scheduling and wording stay editable; the question list does not. */
    public function update(User $user, Exam $exam): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    /**
     * Rule 3 of .claude/rules/domain-kaisan.md. Once an exam has left draft a
     * student may already have seen the paper, so the questions are frozen and
     * a revision means a new exam.
     */
    public function changeQuestions(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam) && $exam->status->allowsQuestionEditing();
    }

    /**
     * Whether this student may sit this exam.
     *
     * It lives here rather than on ExamAttemptPolicy because the subject is an
     * Exam: Laravel picks the policy from the argument's class, and an ability
     * declared on the wrong policy is simply never found -- which reads as a
     * denial and hides the mistake.
     */
    public function start(User $user, Exam $exam): bool
    {
        return $user->isMurid()
            && $user->is_active
            && $exam->status->acceptsSubmissions();
    }

    /** Results carry other students' marks, so this is narrower than view(). */
    public function viewResults(User $user, Exam $exam): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // security.md restricts student data to the classes a teacher takes,
        // but no teacher-to-classroom relation exists in the schema yet, so
        // authorship is the closest honest boundary. Revisit when M4's panel
        // or a later module models teaching assignments.
        return $user->isGuru() && $exam->created_by === $user->id;
    }

    /** An exam nobody has sat yet is a mistake; one with attempts is a record. */
    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam)
            && $exam->status->allowsQuestionEditing()
            && ! $exam->attempts()->exists();
    }
}
