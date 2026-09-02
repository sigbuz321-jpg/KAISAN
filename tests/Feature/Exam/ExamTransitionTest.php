<?php

use App\Actions\SaveAttemptAnswer;
use App\Actions\StartExamAttempt;
use App\Enums\AttemptStatus;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;

beforeEach(function () {
    $this->travelTo(now()->startOfMinute());
    $this->murid = User::factory()->murid()->create();
});

it('opens a scheduled exam once its start time arrives', function () {
    $exam = Exam::factory()->scheduled()->create([
        'starts_at' => now()->addMinute(),
        'ends_at' => now()->addHours(2),
    ]);

    $this->artisan('exams:transition')->assertSuccessful();
    expect($exam->refresh()->status)->toBe(ExamStatus::Scheduled);

    $this->travelTo(now()->addMinutes(2));
    $this->artisan('exams:transition')->assertSuccessful();

    expect($exam->refresh()->status)->toBe(ExamStatus::Active);
});

it('leaves a draft exam alone however long it waits', function () {
    // A draft is not scheduled. Opening it automatically would put a
    // half-written paper in front of students.
    $exam = Exam::factory()->create([
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $this->artisan('exams:transition');

    expect($exam->refresh()->status)->toBe(ExamStatus::Draft);
});

it('closes and grades an exam once its window passes', function () {
    $exam = examWithQuestions(4, [
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinute(),
    ], state: 'active');

    $this->artisan('exams:transition');

    expect($exam->refresh()->status)->toBe(ExamStatus::Graded);
});

it('grades an attempt the student never handed in', function () {
    // Product decision: a student whose connection died keeps the work they
    // did. The PRD promise that answers survive a dropped connection would be
    // empty otherwise.
    $exam = examWithQuestions(4, [
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addMinutes(5),
        'duration_minutes' => 60,
        // Unshuffled, so the answer key doubles as the letter on screen. This
        // test is about grading an abandoned attempt, not about shuffling.
        'shuffle_options' => false,
        'shuffle_questions' => false,
    ], state: 'active');

    $attempt = app(StartExamAttempt::class)->handle($exam, $this->murid);
    $keys = answerKeysOf($exam);
    $first = array_key_first($keys);
    app(SaveAttemptAnswer::class)->handle($attempt, $first, $keys[$first]);

    $this->travelTo(now()->addMinutes(10));
    $this->artisan('exams:transition');

    $attempt->refresh();

    expect($attempt->status)->toBe(AttemptStatus::Submitted)
        ->and($attempt->correct_count)->toBe(1)
        ->and($attempt->score)->toBe('25.00');
});

it('stamps an auto-graded attempt with the moment the time ran out', function () {
    $exam = examWithQuestions(2, [
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addMinutes(5),
        'duration_minutes' => 60,
    ], state: 'active');

    $attempt = app(StartExamAttempt::class)->handle($exam, $this->murid);
    $deadline = $exam->ends_at;

    $this->travelTo(now()->addMinutes(30));
    $this->artisan('exams:transition');

    // Not "whenever the command happened to run" -- when work stopped being
    // possible.
    expect($attempt->refresh()->submitted_at->timestamp)->toBe($deadline->timestamp);
});

it('leaves an already submitted attempt untouched', function () {
    $exam = examWithQuestions(2, [
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinute(),
    ], state: 'active');

    $attempt = ExamAttempt::factory()->submitted()->create([
        'exam_id' => $exam->id,
        'user_id' => $this->murid->id,
        'score' => '75.00',
        'submitted_at' => now()->subMinutes(30),
    ]);

    $before = $attempt->submitted_at->timestamp;

    $this->artisan('exams:transition');

    expect($attempt->refresh()->score)->toBe('75.00')
        ->and($attempt->submitted_at->timestamp)->toBe($before);
});

it('does not grade an exam that is still running', function () {
    $exam = examWithQuestions(2, [
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ], state: 'active');

    app(StartExamAttempt::class)->handle($exam, $this->murid);

    $this->artisan('exams:transition');

    expect($exam->refresh()->status)->toBe(ExamStatus::Active)
        ->and(ExamAttempt::sole()->status)->toBe(AttemptStatus::InProgress);
});

it('creates no record for a student who never opened the exam', function () {
    // Product decision: absent is not the same as zero. A teacher sees "belum
    // mengerjakan", and the ranking is untouched.
    $exam = examWithQuestions(3, [
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinute(),
    ], state: 'active');

    User::factory()->murid()->count(3)->create();

    $this->artisan('exams:transition');

    expect($exam->refresh()->status)->toBe(ExamStatus::Graded)
        ->and(ExamAttempt::count())->toBe(0);
});

it('can run twice without changing anything the second time', function () {
    $exam = examWithQuestions(2, [
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinute(),
    ], state: 'active');

    app(StartExamAttempt::class)->handle($exam, $this->murid);

    $this->artisan('exams:transition');
    $first = ExamAttempt::sole()->only(['score', 'submitted_at', 'status']);

    $this->artisan('exams:transition');

    expect(ExamAttempt::sole()->only(['score', 'submitted_at', 'status']))->toEqual($first);
});
