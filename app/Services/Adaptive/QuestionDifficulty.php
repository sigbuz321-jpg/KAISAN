<?php

namespace App\Services\Adaptive;

/**
 * The question's side of the same exchange.
 *
 * A question's difficulty moves too, but far more slowly than a student's
 * rating, and only once enough students have seen it. A question everyone gets
 * right drifts down to where it belongs; one everyone misses drifts up.
 *
 * The slow K is deliberate. A student's rating should chase their ability; a
 * question's difficulty should stay put unless the evidence is substantial,
 * or the two would chase each other and neither would settle.
 */
class QuestionDifficulty
{
    public const K = 8;

    /** Below this many answers, the evidence is too thin to move anything. */
    public const MIN_ANSWERS = 10;

    /** A question wrong this often is more likely broken than hard. */
    public const SUSPECT_CORRECT_RATE = 0.15;

    public const SUSPECT_AFTER_ANSWERS = 20;

    public function __construct(private readonly EloRating $elo) {}

    /**
     * The question's difficulty after being answered once more.
     *
     * `timesAnswered` is the count *before* this answer.
     */
    public function next(int $difficulty, int $rating, bool $correct, int $timesAnswered): int
    {
        if ($timesAnswered < self::MIN_ANSWERS) {
            return $difficulty;
        }

        // Mirrored: the question "wins" when the student gets it wrong.
        $expected = $this->elo->expectedScore($difficulty, $rating);
        $actual = $correct ? 0 : 1;

        return $this->elo->clamp((int) round($difficulty + self::K * ($actual - $expected)));
    }

    /**
     * Whether a question looks broken rather than hard.
     *
     * A question almost nobody gets right is usually ambiguous, mis-keyed, or
     * missing context -- not difficult. Surfaced to teachers rather than acted
     * on automatically, because only a person can tell those apart.
     */
    public function looksSuspect(int $timesAnswered, int $timesCorrect): bool
    {
        if ($timesAnswered < self::SUSPECT_AFTER_ANSWERS) {
            return false;
        }

        return $timesCorrect / $timesAnswered < self::SUSPECT_CORRECT_RATE;
    }
}
