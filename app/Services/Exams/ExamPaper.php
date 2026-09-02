<?php

namespace App\Services\Exams;

use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * The exam as a student is allowed to see it.
 *
 * This is the only thing that should ever build a student's question list. It
 * drops answer_key and explanation before anything leaves the server, so a
 * leak has to get past one small class rather than every view and endpoint
 * that will ever touch an exam.
 *
 * Shuffling is presentation only. The letter a student clicks is translated
 * back to the question's own lettering before it is stored, so grading always
 * compares like with like and never has to know that shuffling exists.
 */
class ExamPaper
{
    /**
     * @return list<array{id: int, number: int, stem: string, options: array<string, string>}>
     */
    public function forStudent(ExamAttempt $attempt): array
    {
        return $this->questionsFor($attempt)
            ->values()
            ->map(fn (Question $question, int $index) => [
                'id' => $question->id,
                'number' => $index + 1,
                'stem' => $question->stem,
                'options' => $this->displayedOptions($attempt, $question),
            ])
            ->all();
    }

    /**
     * Turns the letter the student clicked into the letter the question was
     * written with. Without this step a shuffled paper would mark a correct
     * answer wrong.
     */
    public function toStoredOption(ExamAttempt $attempt, Question $question, ?string $displayed): ?string
    {
        if ($displayed === null) {
            return null;
        }

        return $this->optionMap($attempt, $question)[$displayed] ?? null;
    }

    /** @return Collection<int, Question> */
    private function questionsFor(ExamAttempt $attempt): Collection
    {
        $questions = $attempt->exam->questions()->get();

        if (! $attempt->exam->shuffle_questions) {
            return $questions;
        }

        return $questions->sortBy(fn (Question $q) => $this->fingerprint($attempt, $q->id))->values();
    }

    /** @return array<string, string> */
    private function displayedOptions(ExamAttempt $attempt, Question $question): array
    {
        $stored = $question->orderedOptions();

        return collect($this->optionMap($attempt, $question))
            ->map(fn (string $original) => $stored[$original] ?? '')
            ->all();
    }

    /**
     * Displayed letter => the question's own letter.
     *
     * Derived from a hash rather than a seeded shuffle: it is stable across
     * PHP versions and touches no global random state, so a student who
     * reloads -- or a worker on another machine grading later -- sees exactly
     * the same arrangement.
     *
     * @return array<string, string>
     */
    private function optionMap(ExamAttempt $attempt, Question $question): array
    {
        $labels = Question::OPTION_KEYS;

        if (! $attempt->exam->shuffle_options) {
            return array_combine($labels, $labels);
        }

        $shuffled = collect($labels)
            ->sortBy(fn (string $label) => $this->fingerprint($attempt, $question->id, $label))
            ->values()
            ->all();

        return array_combine($labels, $shuffled);
    }

    private function fingerprint(ExamAttempt $attempt, int $questionId, string $suffix = ''): string
    {
        return md5($attempt->id.':'.$questionId.':'.$suffix);
    }
}
