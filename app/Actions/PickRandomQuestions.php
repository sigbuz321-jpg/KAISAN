<?php

namespace App\Actions;

use App\Enums\DifficultyBand;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Question;

class PickRandomQuestions
{
    /**
     * Fills a draft exam with published questions drawn at random.
     *
     * Only published questions are eligible, which is what keeps an unreviewed
     * AI draft out of a real exam without this class needing to know that AI
     * exists -- rule 1 of .claude/rules/domain-kaisan.md holds by construction.
     *
     * Returns how many were actually attached, which can be fewer than asked
     * for. The teacher is told rather than quietly given a short paper.
     */
    public function handle(
        Exam $exam,
        int $count,
        ?int $topicId = null,
        ?DifficultyBand $band = null,
    ): int {
        $query = Question::query()
            ->where('subject_id', $exam->subject_id)
            ->where('status', QuestionStatus::Published);

        if ($topicId !== null) {
            $query->where('topic_id', $topicId);
        }

        if ($band !== null) {
            [$from, $to] = $band->eloRange();
            $query->whereBetween('difficulty', [$from, $to]);
        }

        $ids = $query->inRandomOrder()->limit($count)->pluck('id')->all();

        return app(SetExamQuestions::class)->handle($exam, $ids);
    }
}
