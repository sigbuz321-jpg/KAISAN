<?php

namespace App\Actions;

use App\Enums\QuestionStatus;
use App\Exceptions\QuestionWorkflowException;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Moves a selection of questions to one status.
 *
 * Bulk review exists because a teacher working through twenty AI drafts should
 * not have to click twenty times -- rule 1 of domain-kaisan.md asks for their
 * judgement, not for tedium.
 *
 * Each record still goes through ChangeQuestionStatus one at a time rather than
 * a single bulk UPDATE. The workflow guard, the approver stamp and the
 * AI-must-be-reviewed rule are per-record decisions, and a bulk update would
 * skip all three. Selections come from one page of a table, so the loop is
 * bounded by the page size.
 */
class BulkChangeQuestionStatus
{
    public function __construct(private readonly ChangeQuestionStatus $changeStatus) {}

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{changed: int, skipped: int}
     */
    public function handle(Collection $questions, QuestionStatus $target, User $actor): array
    {
        $changed = 0;
        $skipped = 0;

        foreach ($questions as $question) {
            if (! $actor->can('changeStatus', $question)) {
                $skipped++;

                continue;
            }

            try {
                $this->changeStatus->handle($question, $target, $actor);
                $changed++;
            } catch (QuestionWorkflowException) {
                // Wrong starting status for this move, or an AI draft that has
                // not been reviewed. Counted rather than reported one by one:
                // twenty identical warnings help nobody.
                $skipped++;
            }
        }

        return ['changed' => $changed, 'skipped' => $skipped];
    }
}
