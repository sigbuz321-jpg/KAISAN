<?php

use App\Actions\BulkChangeQuestionStatus;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->bulk = app(BulkChangeQuestionStatus::class);
});

it('publishes several AI questions in one go', function () {
    $questions = Question::factory()->fromAi()->review()->count(3)->create();

    $result = $this->bulk->handle($questions, QuestionStatus::Published, $this->guru);

    expect($result)->toBe(['changed' => 3, 'skipped' => 0])
        ->and(Question::where('status', QuestionStatus::Published)->count())->toBe(3);
});

it('stamps the approving teacher on every question it publishes', function () {
    $questions = Question::factory()->fromAi()->review()->count(2)->create();

    $this->bulk->handle($questions, QuestionStatus::Published, $this->guru);

    $approved = Question::where('status', QuestionStatus::Published)->get();

    expect($approved->pluck('approved_by')->unique()->values()->all())->toBe([$this->guru->id])
        ->and($approved->whereNull('approved_at'))->toBeEmpty();
});

it('sends questions back to draft in one go', function () {
    $questions = Question::factory()->fromAi()->review()->count(2)->create();

    $this->bulk->handle($questions, QuestionStatus::Draft, $this->guru);

    // Rejection is not deletion: the wording is kept so it can be fixed.
    expect(Question::where('status', QuestionStatus::Draft)->count())->toBe(2)
        ->and(Question::count())->toBe(2);
});

it('skips a question whose status forbids the move instead of failing the batch', function () {
    $reviewable = Question::factory()->fromAi()->review()->create();
    $archived = Question::factory()->fromAi()->archived()->create();

    $result = $this->bulk->handle(
        collect([$reviewable, $archived]),
        QuestionStatus::Published,
        $this->guru,
    );

    expect($result)->toBe(['changed' => 1, 'skipped' => 1])
        ->and($reviewable->refresh()->status)->toBe(QuestionStatus::Published)
        ->and($archived->refresh()->status)->toBe(QuestionStatus::Archived);
});

it('never lets a bulk approval push an unreviewed AI draft straight to published', function () {
    // Rule 1 of domain-kaisan.md holds even when a teacher selects in bulk.
    $draft = Question::factory()->fromAi()->create();

    $result = $this->bulk->handle(collect([$draft]), QuestionStatus::Published, $this->guru);

    expect($result)->toBe(['changed' => 0, 'skipped' => 1])
        ->and($draft->refresh()->status)->toBe(QuestionStatus::Draft);
});

it('publishes a teacher-written draft in bulk, which needs no second opinion', function () {
    $draft = Question::factory()->count(2)->create();

    $result = $this->bulk->handle($draft, QuestionStatus::Published, $this->guru);

    expect($result)->toBe(['changed' => 2, 'skipped' => 0]);
});

it('skips questions the actor is not allowed to touch', function () {
    $murid = User::factory()->murid()->create();
    $questions = Question::factory()->review()->count(2)->create();

    $result = $this->bulk->handle($questions, QuestionStatus::Published, $murid);

    expect($result)->toBe(['changed' => 0, 'skipped' => 2])
        ->and(Question::where('status', QuestionStatus::Review)->count())->toBe(2);
});
