<?php

namespace App\Services\Adaptive;

/**
 * The rating maths, and nothing else.
 *
 * Pure: no database, no clock, no request. That is what makes the 100% coverage
 * .claude/rules/testing.md asks for here honest rather than a number.
 *
 * Why Elo and not full IRT: IRT is more accurate but needs hundreds of
 * responses per question before it says anything useful. This bimbel has 500
 * students and a question bank that keeps changing. Elo gives most of the
 * benefit for a fraction of the complexity, and can be explained to a teacher
 * in one sentence -- which matters, because they are the ones who will have to
 * defend a student's level to a parent.
 */
class EloRating
{
    /** A student's rating never leaves this range, however long a run they have. */
    public const MIN = 400;

    public const MAX = 2400;

    public const START = 1200;

    /**
     * The chance this student answers this question correctly, 0 to 1.
     *
     * The classic Elo curve: 400 points of difference is roughly a 10:1
     * expectation either way.
     */
    public function expectedScore(int $rating, int $difficulty): float
    {
        return 1 / (1 + 10 ** (($difficulty - $rating) / 400));
    }

    /**
     * How far one answer is allowed to move a rating.
     *
     * It shrinks as a student answers more, so an early run of luck moves them
     * quickly to roughly the right place, and later answers only refine it
     * instead of making the rating swing about.
     */
    public function kFactor(int $answersCount): int
    {
        return match (true) {
            $answersCount < 20 => 40,
            $answersCount < 60 => 24,
            default => 16,
        };
    }

    /**
     * The student's rating after answering one question.
     *
     * Beating a harder question moves the rating a lot; getting an easy one
     * right barely moves it, because it was expected. That asymmetry is the
     * whole point of the model.
     */
    public function nextRating(int $rating, int $difficulty, bool $correct, int $answersCount): int
    {
        $expected = $this->expectedScore($rating, $difficulty);
        $actual = $correct ? 1 : 0;

        $next = $rating + $this->kFactor($answersCount) * ($actual - $expected);

        return $this->clamp((int) round($next));
    }

    public function clamp(int $rating): int
    {
        return max(self::MIN, min(self::MAX, $rating));
    }
}
