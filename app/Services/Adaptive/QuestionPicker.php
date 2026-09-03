<?php

namespace App\Services\Adaptive;

use App\Enums\QuestionStatus;
use App\Models\PracticeAnswer;
use App\Models\Question;
use App\Models\User;

/**
 * Chooses the next question to put in front of a student.
 *
 * Aims at roughly a 70% chance of getting it right: hard enough to be worth
 * doing, easy enough not to make a fourteen-year-old give up. On the Elo curve
 * that sits about 150 points below their rating.
 */
class QuestionPicker
{
    /** Offset from the student's rating that lands near a 70% success chance. */
    public const TARGET_OFFSET = -150;

    /** Tried in order until candidates appear. */
    public const WINDOWS = [100, 250, 400];

    /** A question seen this recently is not worth asking again yet. */
    public const REPEAT_COOLDOWN_DAYS = 30;

    public function pick(User $student, int $subjectId, int $rating): PickedQuestion
    {
        $target = $rating + self::TARGET_OFFSET;
        $recent = $this->recentlyAnswered($student, $subjectId);

        foreach (self::WINDOWS as $window) {
            $question = $this->candidates($subjectId, $recent)
                ->whereBetween('difficulty', [$target - $window, $target + $window])
                // Not the closest match: always picking the nearest question
                // makes practice feel like the same handful over and over.
                ->inRandomOrder()
                ->first();

            if ($question !== null) {
                return new PickedQuestion($question, bankIsThin: false);
            }
        }

        // Nothing near their level. Give them something rather than a dead end,
        // and let the teacher know the bank is thin here.
        $anything = $this->candidates($subjectId, $recent)->inRandomOrder()->first();

        if ($anything !== null) {
            return new PickedQuestion($anything, bankIsThin: true);
        }

        // Everything published has been seen recently. Allow repeats rather
        // than telling a student to come back in a month.
        return new PickedQuestion(
            $this->candidates($subjectId, [])->inRandomOrder()->first(),
            bankIsThin: true,
        );
    }

    /**
     * @param  list<int>  $excludedQuestionIds
     * @return \Illuminate\Database\Eloquent\Builder<Question>
     */
    private function candidates(int $subjectId, array $excludedQuestionIds)
    {
        return Question::query()
            ->where('subject_id', $subjectId)
            // Only published: an unreviewed AI draft must never reach a
            // student, in practice any more than in an exam.
            ->where('status', QuestionStatus::Published)
            ->when($excludedQuestionIds !== [], fn ($q) => $q->whereNotIn('id', $excludedQuestionIds));
    }

    /** @return list<int> */
    private function recentlyAnswered(User $student, int $subjectId): array
    {
        return PracticeAnswer::query()
            ->join('practice_sessions', 'practice_sessions.id', '=', 'practice_answers.practice_session_id')
            ->where('practice_sessions.user_id', $student->id)
            ->where('practice_sessions.subject_id', $subjectId)
            ->where('practice_answers.answered_at', '>=', now()->subDays(self::REPEAT_COOLDOWN_DAYS))
            ->pluck('practice_answers.question_id')
            ->unique()
            ->values()
            ->all();
    }
}
