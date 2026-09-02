<?php

namespace App\Actions;

use App\Enums\AttemptStatus;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Services\Scoring\ScoreCalculator;
use App\Services\Scoring\ScoreResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GradeExamAttempt
{
    public function __construct(private readonly ScoreCalculator $calculator) {}

    /**
     * Marks an attempt, server side, from the answer keys as stored.
     *
     * Nothing about the score comes from the request. The client may send
     * which option was chosen; it never sends whether that option was right.
     */
    public function handle(ExamAttempt $attempt, ?Carbon $submittedAt = null): ScoreResult
    {
        $answerKeys = $attempt->exam->questions()
            ->pluck('answer_key', 'questions.id')
            ->all();

        $selected = $attempt->answers()
            ->pluck('selected_option', 'question_id')
            ->all();

        $result = $this->calculator->calculate($answerKeys, $selected);

        DB::transaction(function () use ($attempt, $result, $submittedAt) {
            $this->markAnswers($attempt, $result);

            $attempt->forceFill([
                'score' => $result->score,
                'correct_count' => $result->correctCount,
                'total_questions' => $result->totalQuestions,
                'submitted_at' => $submittedAt ?? now(),
                'status' => AttemptStatus::Submitted,
            ])->save();
        });

        return $result;
    }

    /**
     * Two statements rather than one per answer: an exam can carry fifty
     * questions and .claude/rules/coding-style.md rules out updating models
     * inside a loop.
     */
    private function markAnswers(ExamAttempt $attempt, ScoreResult $result): void
    {
        $correct = array_keys(array_filter($result->correctness));
        $wrong = array_keys(array_filter($result->correctness, fn (bool $ok) => ! $ok));

        foreach ([[$correct, true], [$wrong, false]] as [$questionIds, $value]) {
            if ($questionIds === []) {
                continue;
            }

            AttemptAnswer::where('exam_attempt_id', $attempt->id)
                ->whereIn('question_id', $questionIds)
                ->update(['is_correct' => $value]);
        }
    }
}
