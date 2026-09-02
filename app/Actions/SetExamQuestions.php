<?php

namespace App\Actions;

use App\Enums\QuestionStatus;
use App\Exceptions\ExamWorkflowException;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class SetExamQuestions
{
    /**
     * Replaces an exam's question list.
     *
     * Only while the exam is a draft. Rule 3 of .claude/rules/domain-kaisan.md:
     * once an exam has left the teacher's desk a student may already have seen
     * the paper, and changing it underneath them would invalidate marks that
     * have already been given.
     *
     * @param  list<int>  $questionIds
     */
    public function handle(Exam $exam, array $questionIds): int
    {
        if (! $exam->status->allowsQuestionEditing()) {
            throw ExamWorkflowException::questionsAreFrozen();
        }

        $usable = Question::query()
            ->whereIn('id', $questionIds)
            ->where('subject_id', $exam->subject_id)
            ->where('status', QuestionStatus::Published)
            ->pluck('id')
            ->all();

        // Keep the order the teacher chose, dropping anything that is not a
        // published question of this subject.
        $ordered = array_values(array_filter(
            $questionIds,
            fn (int $id) => in_array($id, $usable, true),
        ));

        DB::transaction(function () use ($exam, $ordered) {
            $exam->questions()->sync(
                collect($ordered)
                    ->mapWithKeys(fn (int $id, int $index) => [$id => ['order' => $index]])
                    ->all()
            );

            // question_count follows what is actually attached, so the number a
            // student is shown can never disagree with the paper they get.
            $exam->update(['question_count' => count($ordered)]);
        });

        return count($ordered);
    }
}
