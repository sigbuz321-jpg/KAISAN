<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->admin = User::factory()->admin()->create();
    $this->murid = User::factory()->murid()->create();
});

it('stops a student reading another student result', function () {
    // Named specifically in .claude/rules/security.md, and asked for as a test
    // rather than a manual check.
    $other = User::factory()->murid()->create();
    $attempt = ExamAttempt::factory()->submitted()->create(['user_id' => $other->id]);

    expect($this->murid->can('view', $attempt))->toBeFalse();
});

it('lets a student read their own result', function () {
    $attempt = ExamAttempt::factory()->submitted()->create(['user_id' => $this->murid->id]);

    expect($this->murid->can('view', $attempt))->toBeTrue();
});

it('stops a student working on someone else attempt', function () {
    $other = User::factory()->murid()->create();
    $attempt = ExamAttempt::factory()->create(['user_id' => $other->id]);

    expect($this->murid->can('update', $attempt))->toBeFalse()
        ->and($this->murid->can('submit', $attempt))->toBeFalse();
});

it('stops a teacher answering on a student behalf', function () {
    $attempt = ExamAttempt::factory()->create(['user_id' => $this->murid->id]);

    expect($this->guru->can('update', $attempt))->toBeFalse();
});

it('stops a student reopening an attempt they already handed in', function () {
    $attempt = ExamAttempt::factory()->submitted()->create(['user_id' => $this->murid->id]);

    expect($this->murid->can('update', $attempt))->toBeFalse();
});

it('shows an exam creator the results but not another teacher', function () {
    $exam = Exam::factory()->create(['created_by' => $this->guru->id]);
    $stranger = User::factory()->guru()->create();

    expect($this->guru->can('viewResults', $exam))->toBeTrue()
        ->and($stranger->can('viewResults', $exam))->toBeFalse()
        ->and($this->admin->can('viewResults', $exam))->toBeTrue();
});

it('hides a draft exam from students', function () {
    $draft = Exam::factory()->create();
    $scheduled = Exam::factory()->scheduled()->create();

    // Both aimed at this student's class, so only the status differs.
    $draft->classrooms()->attach($this->murid->classroom_id);
    $scheduled->classrooms()->attach($this->murid->classroom_id);

    expect($this->murid->can('view', $draft))->toBeFalse()
        ->and($this->murid->can('view', $scheduled))->toBeTrue();
});

it('freezes the question list once an exam leaves draft', function () {
    // Rule 3 of domain-kaisan.md: a revision means a new exam.
    $draft = Exam::factory()->create(['created_by' => $this->guru->id]);
    $active = Exam::factory()->active()->create(['created_by' => $this->guru->id]);

    expect($this->guru->can('changeQuestions', $draft))->toBeTrue()
        ->and($this->guru->can('changeQuestions', $active))->toBeFalse();
});

it('refuses to delete an exam students have already sat', function () {
    $exam = Exam::factory()->create(['created_by' => $this->guru->id]);
    expect($this->guru->can('delete', $exam))->toBeTrue();

    ExamAttempt::factory()->create(['exam_id' => $exam->id]);

    expect($this->guru->can('delete', $exam->refresh()))->toBeFalse();
});

it('never allows deleting a result, not even for the admin', function () {
    // Rule 6: results are voided with a reason, never removed.
    $attempt = ExamAttempt::factory()->submitted()->create();

    expect($this->admin->can('delete', $attempt))->toBeFalse()
        ->and($this->guru->can('delete', $attempt))->toBeFalse();
});

it('keeps a deactivated student out of an exam', function () {
    $exam = Exam::factory()->active()->create();
    $suspended = User::factory()->murid()->inactive()->create();

    expect($suspended->can('start', $exam))->toBeFalse();
});

it('stops anyone starting an exam that is not running', function () {
    // Each exam is aimed at this student's class, so status is the only thing
    // left deciding. Class targeting has its own tests.
    $aimedHere = fn (Exam $exam) => tap($exam, fn (Exam $e) => $e->classrooms()->attach($this->murid->classroom_id));

    $scheduled = $aimedHere(Exam::factory()->scheduled()->create());
    $closed = $aimedHere(Exam::factory()->closed()->create());
    $running = $aimedHere(Exam::factory()->active()->create());

    expect($this->murid->can('start', $scheduled))->toBeFalse()
        ->and($this->murid->can('start', $closed))->toBeFalse()
        ->and($this->murid->can('start', $running))->toBeTrue();
});
