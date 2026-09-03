<?php

namespace App\Services\Adaptive;

use App\Enums\AbilityLevel;

/**
 * What happened after one practice answer.
 *
 * Unlike an exam, practice tells the student straight away whether they were
 * right and why -- that immediate feedback is the point of practising. The
 * rating numbers are here for the server's own use and for the record; what
 * reaches the screen is the level.
 */
readonly class PracticeOutcome
{
    public function __construct(
        public bool $correct,
        public string $answerKey,
        public ?string $explanation,
        public int $ratingBefore,
        public int $ratingAfter,
    ) {}

    public function levelBefore(): AbilityLevel
    {
        return AbilityLevel::forRating($this->ratingBefore);
    }

    public function levelAfter(): AbilityLevel
    {
        return AbilityLevel::forRating($this->ratingAfter);
    }

    /** Worth telling a student about; a few points of rating are not. */
    public function levelChanged(): bool
    {
        return $this->levelBefore() !== $this->levelAfter();
    }
}
