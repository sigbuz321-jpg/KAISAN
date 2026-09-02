<?php

namespace App\Services\Exams;

use App\Models\ExamAttempt;
use Illuminate\Support\Carbon;

/**
 * Decides when a student's time is up.
 *
 * The server owns this answer completely. The countdown a student sees is a
 * picture of it, never the source: a clock can be wrong, paused, or edited.
 */
class ExamWindow
{
    /**
     * Allowance for a submission that left the browser in time but arrived
     * late. It covers network latency and nothing else -- it is not extra
     * working time, and it is not a grace period for a student who is still
     * typing.
     */
    public const LATE_TOLERANCE_SECONDS = 30;

    /**
     * The earlier of "the student's own allotted time" and "the exam closes".
     *
     * A student who starts ten minutes before the exam closes gets ten
     * minutes, not the full duration. That is deliberate: the alternative
     * lets a late starter work past the closing time everyone else respected.
     */
    public function deadlineFor(ExamAttempt $attempt): Carbon
    {
        $byDuration = $attempt->started_at->copy()->addMinutes($attempt->exam->duration_minutes);

        return $byDuration->min($attempt->exam->ends_at);
    }

    /** True once a submission would arrive too late to be accepted. */
    public function hasExpired(ExamAttempt $attempt, ?Carbon $at = null): bool
    {
        $latestAccepted = $this->deadlineFor($attempt)->addSeconds(self::LATE_TOLERANCE_SECONDS);

        return ($at ?? now())->greaterThan($latestAccepted);
    }

    /**
     * Whole seconds left, for the countdown the student sees. Never negative:
     * a finished exam shows zero rather than a negative number.
     */
    public function secondsRemaining(ExamAttempt $attempt, ?Carbon $at = null): int
    {
        $remaining = ($at ?? now())->diffInSeconds($this->deadlineFor($attempt), absolute: false);

        return (int) max(0, floor($remaining));
    }
}
