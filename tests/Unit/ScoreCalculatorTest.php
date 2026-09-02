<?php

use App\Services\Scoring\ScoreCalculator;

beforeEach(function () {
    $this->calculator = new ScoreCalculator;
});

it('scores a perfect paper as one hundred', function () {
    $result = $this->calculator->calculate(
        [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'],
        [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'],
    );

    expect($result->score)->toBe('100.00')
        ->and($result->correctCount)->toBe(4)
        ->and($result->totalQuestions)->toBe(4);
});

it('scores an empty paper as zero', function () {
    $result = $this->calculator->calculate([1 => 'A', 2 => 'B'], []);

    expect($result->score)->toBe('0.00')
        ->and($result->correctCount)->toBe(0);
});

it('counts an unanswered question as wrong rather than leaving it out', function () {
    // Dropping it from the denominator would mean a student who answers one
    // question correctly and skips the rest scores 100.
    $result = $this->calculator->calculate(
        [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'],
        [1 => 'A'],
    );

    expect($result->score)->toBe('25.00')
        ->and($result->totalQuestions)->toBe(4);
});

it('treats an explicitly blank answer as wrong', function () {
    $result = $this->calculator->calculate([1 => 'A', 2 => 'B'], [1 => null, 2 => 'B']);

    expect($result->correctCount)->toBe(1)
        ->and($result->score)->toBe('50.00');
});

it('rounds to two decimals', function () {
    // One right out of three is 33.333...
    $result = $this->calculator->calculate([1 => 'A', 2 => 'B', 3 => 'C'], [1 => 'A']);

    expect($result->score)->toBe('33.33');
});

it('rounds a two thirds score to two decimals', function () {
    $result = $this->calculator->calculate([1 => 'A', 2 => 'B', 3 => 'C'], [1 => 'A', 2 => 'B']);

    expect($result->score)->toBe('66.67');
});

it('reports which questions were right', function () {
    $result = $this->calculator->calculate(
        [7 => 'A', 8 => 'B', 9 => 'C'],
        [7 => 'A', 8 => 'D'],
    );

    expect($result->correctness)->toBe([7 => true, 8 => false, 9 => false]);
});

it('ignores answers to questions that are not on the paper', function () {
    // A tampered request naming a question from another exam must not add to
    // the score, and must not enlarge the denominator either.
    $result = $this->calculator->calculate([1 => 'A'], [1 => 'A', 999 => 'A']);

    expect($result->correctCount)->toBe(1)
        ->and($result->totalQuestions)->toBe(1)
        ->and($result->score)->toBe('100.00');
});

it('returns zero rather than dividing by zero for an empty paper', function () {
    $result = $this->calculator->calculate([], []);

    expect($result->score)->toBe('0.00')
        ->and($result->totalQuestions)->toBe(0)
        ->and($result->correctness)->toBe([]);
});

it('is case sensitive about the chosen option', function () {
    // Options are stored as uppercase A-D and a CHECK constraint enforces it,
    // so a lowercase value is a tampered payload, not a near miss.
    $result = $this->calculator->calculate([1 => 'A'], [1 => 'a']);

    expect($result->correctCount)->toBe(0);
});
