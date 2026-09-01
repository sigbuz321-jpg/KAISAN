<?php

use App\Actions\ChangeQuestionStatus;
use App\Enums\QuestionStatus;
use App\Exceptions\QuestionWorkflowException;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->action = app(ChangeQuestionStatus::class);
});

it('publishes a teacher-written draft directly', function () {
    $question = Question::factory()->create();

    $this->action->handle($question, QuestionStatus::Published, $this->guru);

    expect($question->refresh()->status)->toBe(QuestionStatus::Published)
        ->and($question->approved_by)->toBe($this->guru->id)
        ->and($question->approved_at)->not->toBeNull();
});

it('refuses to publish an AI draft without review', function () {
    $question = Question::factory()->fromAi()->create();

    expect(fn () => $this->action->handle($question, QuestionStatus::Published, $this->guru))
        ->toThrow(QuestionWorkflowException::class, 'Soal hasil AI harus ditinjau lebih dulu');

    expect($question->refresh()->status)->toBe(QuestionStatus::Draft);
});

it('publishes an AI question once it has been through review', function () {
    $question = Question::factory()->fromAi()->review()->create();

    $this->action->handle($question, QuestionStatus::Published, $this->guru);

    expect($question->refresh()->status)->toBe(QuestionStatus::Published);
});

it('refuses to move an archived question straight back to published', function () {
    $question = Question::factory()->archived()->create();

    expect(fn () => $this->action->handle($question, QuestionStatus::Published, $this->guru))
        ->toThrow(QuestionWorkflowException::class);
});

it('drops the old approval when a question goes back to draft', function () {
    $question = Question::factory()->published()->create(['approved_by' => $this->guru->id]);

    $this->action->handle($question, QuestionStatus::Archived, $this->guru);
    $this->action->handle($question, QuestionStatus::Draft, $this->guru);

    expect($question->refresh()->approved_by)->toBeNull()
        ->and($question->approved_at)->toBeNull();
});

it('does nothing when the target status is the current one', function () {
    $question = Question::factory()->published()->create();
    $before = $question->updated_at;

    $this->action->handle($question, QuestionStatus::Published, $this->guru);

    expect($question->refresh()->updated_at->eq($before))->toBeTrue();
});

it('allows archiving from every live status', function () {
    foreach ([QuestionStatus::Draft, QuestionStatus::Review, QuestionStatus::Published] as $from) {
        expect($from->canMoveTo(QuestionStatus::Archived))->toBeTrue();
    }
});
