<?php

namespace App\Actions;

use App\Enums\QuestionStatus;
use App\Exceptions\PracticeException;
use App\Models\PracticeAnswer;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\StudentAbility;
use App\Services\Adaptive\EloRating;
use App\Services\Adaptive\PracticeOutcome;
use App\Services\Adaptive\QuestionDifficulty;
use Illuminate\Support\Facades\DB;

class AnswerPracticeQuestion
{
    public function __construct(
        private readonly EloRating $elo,
        private readonly QuestionDifficulty $difficulty,
    ) {}

    /**
     * Records one practice answer and moves both ratings.
     *
     * Everything happens in one transaction: the answer, the student's rating,
     * the question's difficulty and the session tally. A crash halfway would
     * otherwise leave a student whose level moved for an answer that was never
     * stored, which nobody could later explain.
     */
    public function handle(PracticeSession $session, Question $question, string $option): PracticeOutcome
    {
        $this->guard($session, $question);

        return DB::transaction(function () use ($session, $question, $option) {
            // Locked for the length of the transaction: two tabs answering at
            // once must not both read the same rating and each apply their own
            // change to it.
            $ability = StudentAbility::query()
                ->where('user_id', $session->user_id)
                ->where('subject_id', $session->subject_id)
                ->lockForUpdate()
                ->firstOrFail();

            $correct = $option === $question->answer_key;
            $before = $ability->rating;

            $after = $this->elo->nextRating($before, $question->difficulty, $correct, $ability->answers_count);

            PracticeAnswer::create([
                'practice_session_id' => $session->id,
                'question_id' => $question->id,
                'selected_option' => $option,
                'is_correct' => $correct,
                'rating_before' => $before,
                'rating_after' => $after,
                'answered_at' => now(),
            ]);

            $ability->forceFill([
                'rating' => $after,
                'answers_count' => $ability->answers_count + 1,
                'last_practiced_at' => now(),
            ])->save();

            $this->updateQuestion($question, $before, $correct);

            $session->forceFill([
                'questions_count' => $session->questions_count + 1,
                'correct_count' => $session->correct_count + ($correct ? 1 : 0),
            ])->save();

            return new PracticeOutcome(
                correct: $correct,
                answerKey: $question->answer_key,
                explanation: $question->explanation,
                ratingBefore: $before,
                ratingAfter: $after,
            );
        });
    }

    /**
     * The question's own difficulty drifts towards where the evidence puts it,
     * far more slowly than the student's rating moves.
     */
    private function updateQuestion(Question $question, int $studentRating, bool $correct): void
    {
        $question->forceFill([
            'difficulty' => $this->difficulty->next(
                $question->difficulty,
                $studentRating,
                $correct,
                $question->times_answered,
            ),
            'times_answered' => $question->times_answered + 1,
            'times_correct' => $question->times_correct + ($correct ? 1 : 0),
        ])->save();
    }

    private function guard(PracticeSession $session, Question $question): void
    {
        if (! $session->isOpen()) {
            throw PracticeException::sessionClosed();
        }

        if ($question->subject_id !== $session->subject_id) {
            throw PracticeException::wrongSubject();
        }

        if ($question->status !== QuestionStatus::Published) {
            throw PracticeException::notPublished();
        }
    }
}
