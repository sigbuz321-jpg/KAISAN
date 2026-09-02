<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The teacher asked for more than the cost guards allow.
 *
 * Not a security error and not a router error -- a budget one. The message is
 * shown to the teacher as-is, so it says what to do next.
 */
class AiQuotaException extends RuntimeException
{
    public static function tooManyJobs(int $perHour): self
    {
        return new self(
            "Anda sudah mencapai batas {$perHour} permintaan soal AI dalam satu jam. ".
            'Silakan coba lagi nanti.'
        );
    }

    public static function tooManyQuestions(int $max): self
    {
        return new self("Satu permintaan paling banyak {$max} soal.");
    }
}
