<?php

namespace App\Services\Adaptive;

use App\Models\Question;

/**
 * The chosen question, plus whether the bank could actually serve the request.
 *
 * `bankIsThin` is true when nothing existed near the student's level and the
 * picker had to reach outside it. That is a message for the teacher -- the
 * subject needs more questions at that difficulty -- and never for the student,
 * who should just get on with practising.
 */
readonly class PickedQuestion
{
    public function __construct(
        public ?Question $question,
        public bool $bankIsThin,
    ) {}

    public function found(): bool
    {
        return $this->question !== null;
    }
}
