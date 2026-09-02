<?php

namespace App\Services\Scoring;

/**
 * The outcome of grading one attempt.
 *
 * The raw counts travel alongside the percentage on purpose: if the formula
 * ever changes, every past result can be recomputed from what was stored.
 */
readonly class ScoreResult
{
    /** @param array<int, bool> $correctness question id => whether it was right */
    public function __construct(
        public int $correctCount,
        public int $totalQuestions,
        public string $score,
        public array $correctness,
    ) {}
}
