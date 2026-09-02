<?php

namespace App\Services\Scoring;

/**
 * Turns answers into a score.
 *
 * Deliberately pure: it takes two plain arrays and touches no database, no
 * clock and no request. Everything that decides a student's mark is visible in
 * one short method, and it can be tested exhaustively -- .claude/rules/testing.md
 * asks for 100% coverage here, and this is the shape that makes that honest.
 *
 * The score is never accepted from the client. This runs on submit, server
 * side, from the answer keys as stored.
 */
class ScoreCalculator
{
    /**
     * @param  array<int, string>  $answerKeys  question id => 'A'|'B'|'C'|'D'
     * @param  array<int, string|null>  $selected  question id => chosen option, or null
     */
    public function calculate(array $answerKeys, array $selected): ScoreResult
    {
        $correctness = [];
        $correct = 0;

        foreach ($answerKeys as $questionId => $key) {
            // A question the student never reached counts as wrong. Leaving it
            // out of the denominator instead would reward giving up early.
            $isCorrect = ($selected[$questionId] ?? null) === $key;

            $correctness[$questionId] = $isCorrect;

            if ($isCorrect) {
                $correct++;
            }
        }

        $total = count($answerKeys);

        return new ScoreResult(
            correctCount: $correct,
            totalQuestions: $total,
            score: $this->percentage($correct, $total),
            correctness: $correctness,
        );
    }

    /**
     * An exam with no questions cannot be scheduled, so this should never see
     * a zero total. It returns 0.00 rather than dividing by it, because a
     * scoring routine is the wrong place to discover a broken exam.
     */
    private function percentage(int $correct, int $total): string
    {
        if ($total === 0) {
            return '0.00';
        }

        return number_format($correct / $total * 100, 2, '.', '');
    }
}
