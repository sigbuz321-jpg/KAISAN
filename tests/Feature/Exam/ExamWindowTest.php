<?php

use App\Models\ExamAttempt;
use App\Services\Exams\ExamWindow;

beforeEach(function () {
    $this->window = app(ExamWindow::class);
    $this->travelTo(now()->startOfMinute());
});

it('ends the attempt after the exam duration when there is time to spare', function () {
    $exam = examWithQuestions(2, [
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addHours(3),
        'duration_minutes' => 60,
    ]);

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'started_at' => now(),
    ]);

    expect($this->window->deadlineFor($attempt)->timestamp)
        ->toBe(now()->addMinutes(60)->timestamp);
});

it('cuts the attempt short when the exam closes first', function () {
    // A student starting ten minutes before closing gets ten minutes, not the
    // full hour. Otherwise a late starter works past the time everyone else
    // respected.
    $exam = examWithQuestions(2, [
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addMinutes(10),
        'duration_minutes' => 60,
    ]);

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'started_at' => now(),
    ]);

    expect($this->window->deadlineFor($attempt)->timestamp)
        ->toBe($exam->ends_at->timestamp);
});

it('accepts a submission in the last second', function () {
    $attempt = attemptEndingIn(60);

    $this->travelTo(now()->addSeconds(59));

    expect($this->window->hasExpired($attempt))->toBeFalse();
});

it('accepts a submission inside the thirty second latency allowance', function () {
    $attempt = attemptEndingIn(60);

    $this->travelTo(now()->addSeconds(85));

    expect($this->window->hasExpired($attempt))->toBeFalse();
});

it('rejects a submission thirty one seconds past the deadline', function () {
    $attempt = attemptEndingIn(60);

    $this->travelTo(now()->addSeconds(91));

    expect($this->window->hasExpired($attempt))->toBeTrue();
});

it('counts down in whole seconds', function () {
    $attempt = attemptEndingIn(600);

    $this->travelTo(now()->addSeconds(90));

    expect($this->window->secondsRemaining($attempt))->toBe(510);
});

it('never shows a negative countdown', function () {
    $attempt = attemptEndingIn(60);

    $this->travelTo(now()->addHours(2));

    expect($this->window->secondsRemaining($attempt))->toBe(0);
});

it('keeps counting from the server start time after a reconnect', function () {
    // The student reloads twenty minutes in. Their remaining time is measured
    // from started_at, not from when the page came back.
    $attempt = attemptEndingIn(3_600);

    $this->travelTo(now()->addMinutes(20));

    expect($this->window->secondsRemaining($attempt))->toBe(2_400);
});

/** An in-progress attempt whose deadline is exactly $seconds from now. */
function attemptEndingIn(int $seconds): ExamAttempt
{
    $exam = examWithQuestions(2, [
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'duration_minutes' => (int) ceil($seconds / 60),
    ]);

    return ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'started_at' => now(),
    ]);
}
