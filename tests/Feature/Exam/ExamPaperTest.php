<?php

use App\Actions\SaveAttemptAnswer;
use App\Actions\StartExamAttempt;
use App\Actions\SubmitExamAttempt;
use App\Models\User;
use App\Services\Exams\ExamPaper;

beforeEach(function () {
    $this->paper = app(ExamPaper::class);
    $this->start = app(StartExamAttempt::class);
    $this->save = app(SaveAttemptAnswer::class);
    $this->submit = app(SubmitExamAttempt::class);
    $this->murid = User::factory()->murid()->create();
});

it('never puts the answer key in the student paper', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);

    $paper = $this->paper->forStudent($attempt);
    $serialised = json_encode($paper);

    expect($paper)->toHaveCount(4)
        ->and($serialised)->not->toContain('answer_key')
        ->and($serialised)->not->toContain('explanation');

    foreach ($paper as $question) {
        expect(array_keys($question))->toBe(['id', 'number', 'stem', 'options']);
    }
});

it('never puts the explanation in the student paper', function () {
    $exam = examWithQuestions(2);
    $exam->questions()->first()->update(['explanation' => 'RAHASIA PEMBAHASAN']);

    $attempt = $this->start->handle($exam, $this->murid);

    expect(json_encode($this->paper->forStudent($attempt)))->not->toContain('RAHASIA PEMBAHASAN');
});

it('gives every question exactly four lettered options', function () {
    $exam = examWithQuestions(3);
    $attempt = $this->start->handle($exam, $this->murid);

    foreach ($this->paper->forStudent($attempt) as $question) {
        expect(array_keys($question['options']))->toBe(['A', 'B', 'C', 'D']);
    }
});

it('shows the same paper in the same order after a reload', function () {
    $exam = examWithQuestions(6);
    $attempt = $this->start->handle($exam, $this->murid);

    // A student who reloads must not think they lost their place.
    expect($this->paper->forStudent($attempt))
        ->toBe($this->paper->forStudent($attempt->fresh()));
});

it('gives two students different question orders', function () {
    $exam = examWithQuestions(8);
    $other = User::factory()->murid()->create();

    $mine = collect($this->paper->forStudent($this->start->handle($exam, $this->murid)))->pluck('id');
    $theirs = collect($this->paper->forStudent($this->start->handle($exam, $other)))->pluck('id');

    expect($mine->all())->not->toBe($theirs->all())
        ->and($mine->sort()->values()->all())->toBe($theirs->sort()->values()->all());
});

it('keeps the question order fixed when shuffling is off', function () {
    $exam = examWithQuestions(5, ['shuffle_questions' => false]);
    $attempt = $this->start->handle($exam, $this->murid);

    expect(collect($this->paper->forStudent($attempt))->pluck('id')->all())
        ->toBe($exam->questions()->pluck('questions.id')->all());
});

it('scores a shuffled paper correctly', function () {
    // The bug this guards against: with options shuffled, the letter the
    // student clicks is not the letter the question was written with. Storing
    // the clicked letter as-is would mark a correct answer wrong.
    $exam = examWithQuestions(4, ['shuffle_options' => true]);
    $attempt = $this->start->handle($exam, $this->murid);

    foreach ($this->paper->forStudent($attempt) as $shown) {
        $question = $exam->questions()->whereKey($shown['id'])->sole();
        $correctText = $question->orderedOptions()[$question->answer_key];

        // Click whichever letter is showing the right text.
        $clicked = array_search($correctText, $shown['options'], true);

        $this->save->handle($attempt, $shown['id'], $clicked);
    }

    expect($this->submit->handle($attempt)->score)->toBe('100.00');
});

it('stores the question own letter, not the letter on screen', function () {
    $exam = examWithQuestions(4, ['shuffle_options' => true]);
    $attempt = $this->start->handle($exam, $this->murid);

    $shown = $this->paper->forStudent($attempt)[0];
    $question = $exam->questions()->whereKey($shown['id'])->sole();
    $clickedText = $shown['options']['A'];

    $this->save->handle($attempt, $shown['id'], 'A');

    $storedLetter = $attempt->answers()->sole()->selected_option;

    expect($question->orderedOptions()[$storedLetter])->toBe($clickedText);
});

it('scores an unshuffled paper correctly too', function () {
    $exam = examWithQuestions(4, ['shuffle_options' => false, 'shuffle_questions' => false]);
    $attempt = $this->start->handle($exam, $this->murid);

    foreach (answerKeysOf($exam) as $questionId => $key) {
        $this->save->handle($attempt, $questionId, $key);
    }

    expect($this->submit->handle($attempt)->score)->toBe('100.00');
});
