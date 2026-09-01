<?php

namespace App\Actions;

use App\Enums\QuestionStatus;
use App\Exceptions\QuestionWorkflowException;
use App\Models\Question;
use App\Models\User;

class ChangeQuestionStatus
{
    public function handle(Question $question, QuestionStatus $target, User $actor): Question
    {
        $current = $question->status;

        if ($current === $target) {
            return $question;
        }

        if (! $current->canMoveTo($target)) {
            throw new QuestionWorkflowException(
                "Soal berstatus \"{$current->label()}\" tidak bisa langsung dipindahkan ke \"{$target->label()}\"."
            );
        }

        // Rule 1 of .claude/rules/domain-kaisan.md. The enum allows draft to
        // published because a teacher writing their own question needs no
        // second opinion; an AI draft always does.
        if ($target === QuestionStatus::Published
            && $current === QuestionStatus::Draft
            && ! $question->source->mayPublishWithoutReview()) {
            throw new QuestionWorkflowException(
                'Soal hasil AI harus ditinjau lebih dulu sebelum diterbitkan.'
            );
        }

        $question->status = $target;

        if ($target === QuestionStatus::Published) {
            $question->approved_by = $actor->id;
            $question->approved_at = now();
        }

        // Sending a question back to the start drops the old approval, so the
        // record never claims someone approved wording they never saw.
        if ($target === QuestionStatus::Draft) {
            $question->approved_by = null;
            $question->approved_at = null;
        }

        $question->save();

        return $question;
    }
}
