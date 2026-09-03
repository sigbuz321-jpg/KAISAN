<?php

use App\Services\Adaptive\EloRating;
use App\Services\Adaptive\QuestionDifficulty;

beforeEach(function () {
    $this->difficulty = new QuestionDifficulty(new EloRating);
});

it('leaves a question alone until enough students have seen it', function () {
    // Below ten answers the evidence is too thin to move anything.
    expect($this->difficulty->next(1200, 1600, correct: true, timesAnswered: 9))->toBe(1200);
});

it('lowers a question everyone gets right', function () {
    $after = $this->difficulty->next(1200, 1200, correct: true, timesAnswered: 50);

    expect($after)->toBeLessThan(1200);
});

it('raises a question everyone gets wrong', function () {
    $after = $this->difficulty->next(1200, 1200, correct: false, timesAnswered: 50);

    expect($after)->toBeGreaterThan(1200);
});

it('moves a question far more slowly than a student', function () {
    // Otherwise the two chase each other and neither settles.
    $elo = new EloRating;

    $questionShift = abs($this->difficulty->next(1200, 1200, true, 50) - 1200);
    $studentShift = abs($elo->nextRating(1200, 1200, true, 50) - 1200);

    expect($questionShift)->toBeLessThan($studentShift);
});

it('keeps a question inside the rating bounds', function () {
    expect($this->difficulty->next(EloRating::MAX, 400, correct: false, timesAnswered: 100))
        ->toBe(EloRating::MAX)
        ->and($this->difficulty->next(EloRating::MIN, 2400, correct: true, timesAnswered: 100))
        ->toBe(EloRating::MIN);
});

it('does not flag a question too few students have tried', function () {
    expect($this->difficulty->looksSuspect(timesAnswered: 19, timesCorrect: 0))->toBeFalse();
});

it('flags a question almost nobody gets right', function () {
    // Below 15% correct after 20 tries, it is more likely broken than hard.
    expect($this->difficulty->looksSuspect(timesAnswered: 20, timesCorrect: 2))->toBeTrue();
});

it('does not flag a merely hard question', function () {
    expect($this->difficulty->looksSuspect(timesAnswered: 20, timesCorrect: 3))->toBeFalse();
});

it('does not flag a question everyone answers correctly', function () {
    expect($this->difficulty->looksSuspect(timesAnswered: 100, timesCorrect: 95))->toBeFalse();
});
