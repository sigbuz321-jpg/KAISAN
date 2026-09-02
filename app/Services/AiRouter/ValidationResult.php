<?php

namespace App\Services\AiRouter;

/**
 * The outcome of screening one batch of candidates.
 *
 * Rejections are counted and their reasons kept so the teacher can be told
 * "18 dari 20 soal tersimpan" rather than being left to wonder.
 */
readonly class ValidationResult
{
    /**
     * @param  list<array<string, mixed>>  $accepted
     * @param  list<string>  $reasons
     */
    public function __construct(
        public array $accepted,
        public array $reasons,
    ) {}

    public function acceptedCount(): int
    {
        return count($this->accepted);
    }

    public function rejectedCount(): int
    {
        return count($this->reasons);
    }
}
