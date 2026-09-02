<?php

use App\Enums\QuestionStatus;
use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->actingAs($this->guru);
});

it('publishes several AI questions in one go', function () {
    $questions = Question::factory()->fromAi()->review()->count(3)->create();

    Livewire::test(ListQuestions::class)
        ->callAction('setujuiMassal', arguments: ['records' => $questions->pluck('id')->all()]);

    expect(Question::where('status', QuestionStatus::Published)->count())->toBe(3);
});

it('stamps the approving teacher on every question it publishes', function () {
    $questions = Question::factory()->fromAi()->review()->count(2)->create();

    Livewire::test(ListQuestions::class)
        ->callAction('setujuiMassal', arguments: ['records' => $questions->pluck('id')->all()]);

    $approved = Question::where('status', QuestionStatus::Published)->get();

    expect($approved)->toHaveCount(2)
        ->and($approved->pluck('approved_by')->unique()->all())->toBe([$this->guru->id]);
});

it('sends questions back to draft in one go', function () {
    $questions = Question::factory()->fromAi()->review()->count(2)->create();

    Livewire::test(ListQuestions::class)
        ->callAction('tolakMassal', arguments: ['records' => $questions->pluck('id')->all()]);

    expect(Question::where('status', QuestionStatus::Draft)->count())->toBe(2)
        // Rejection is not deletion: the wording is kept so it can be fixed.
        ->and(Question::count())->toBe(2);
});

it('skips questions whose status does not allow the move instead of failing the batch', function () {
    $reviewable = Question::factory()->fromAi()->review()->create();
    $archived = Question::factory()->fromAi()->archived()->create();

    Livewire::test(ListQuestions::class)
        ->callAction('setujuiMassal', arguments: [
            'records' => [$reviewable->id, $archived->id],
        ]);

    expect($reviewable->refresh()->status)->toBe(QuestionStatus::Published)
        ->and($archived->refresh()->status)->toBe(QuestionStatus::Archived);
});

it('never lets a bulk approval push an AI draft straight past review', function () {
    // The AI question here has never been through review. Rule 1 of
    // domain-kaisan.md holds even when the teacher selects it in bulk.
    $draft = Question::factory()->fromAi()->create();

    Livewire::test(ListQuestions::class)
        ->callAction('setujuiMassal', arguments: ['records' => [$draft->id]]);

    expect($draft->refresh()->status)->toBe(QuestionStatus::Draft);
});
