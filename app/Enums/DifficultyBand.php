<?php

namespace App\Enums;

/**
 * The teacher-facing difficulty of a generation request.
 *
 * Questions store difficulty on the Elo scale (see questions.difficulty, which
 * starts at 1200) because the adaptive engine in M5 needs a continuous number
 * it can nudge up and down. Teachers do not think in Elo, so a request names a
 * band and this enum decides where on the scale a fresh question starts.
 */
enum DifficultyBand: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    /**
     * 200 points either side of the 1200 baseline. On the Elo curve that is
     * roughly a 76% / 50% / 24% chance of a median student answering correctly,
     * which matches what teachers expect from "mudah", "sedang" and "sulit".
     */
    public const BASELINE = 1200;

    public const SPREAD = 200;

    public function toElo(): int
    {
        return match ($this) {
            self::Easy => self::BASELINE - self::SPREAD,
            self::Medium => self::BASELINE,
            self::Hard => self::BASELINE + self::SPREAD,
        };
    }

    /**
     * The Elo span a band covers when picking existing questions.
     *
     * Wider than the single point toElo() starts a new question at: a question
     * the adaptive engine has since nudged to 1180 is still a "sedang" one, and
     * a teacher asking for medium questions should get it.
     *
     * @return array{0: int, 1: int}
     */
    public function eloRange(): array
    {
        $half = (int) (self::SPREAD / 2);

        return match ($this) {
            self::Easy => [0, self::BASELINE - $half],
            self::Medium => [self::BASELINE - $half + 1, self::BASELINE + $half],
            self::Hard => [self::BASELINE + $half + 1, 9999],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Mudah',
            self::Medium => 'Sedang',
            self::Hard => 'Sulit',
        };
    }

    /** The word handed to the AI router, kept in English with the rest of the prompt contract. */
    public function promptWord(): string
    {
        return $this->value;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $b) => [$b->value => $b->label()])
            ->all();
    }
}
