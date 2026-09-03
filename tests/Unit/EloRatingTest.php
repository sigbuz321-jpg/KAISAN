<?php

use App\Services\Adaptive\EloRating;

beforeEach(function () {
    $this->elo = new EloRating;
});

it('gives an even chance against an equally rated question', function () {
    expect($this->elo->expectedScore(1200, 1200))->toBe(0.5);
});

it('expects a strong student to beat an easy question', function () {
    expect($this->elo->expectedScore(1600, 1200))->toBeGreaterThan(0.9);
});

it('expects a weak student to miss a hard question', function () {
    expect($this->elo->expectedScore(800, 1400))->toBeLessThan(0.1);
});

it('shrinks the step after twenty answers and again after sixty', function () {
    // A new student should reach roughly the right level fast; an established
    // one should not swing about on a single answer.
    expect($this->elo->kFactor(0))->toBe(40)
        ->and($this->elo->kFactor(19))->toBe(40)
        ->and($this->elo->kFactor(20))->toBe(24)
        ->and($this->elo->kFactor(59))->toBe(24)
        ->and($this->elo->kFactor(60))->toBe(16)
        ->and($this->elo->kFactor(500))->toBe(16);
});

it('raises the rating after a correct answer', function () {
    expect($this->elo->nextRating(1200, 1200, correct: true, answersCount: 0))
        ->toBeGreaterThan(1200);
});

it('lowers the rating after a wrong answer', function () {
    expect($this->elo->nextRating(1200, 1200, correct: false, answersCount: 0))
        ->toBeLessThan(1200);
});

it('rewards beating a harder question more than an easier one', function () {
    $hard = $this->elo->nextRating(1200, 1500, correct: true, answersCount: 0);
    $easy = $this->elo->nextRating(1200, 900, correct: true, answersCount: 0);

    expect($hard - 1200)->toBeGreaterThan($easy - 1200);
});

it('barely moves the rating for an expected win', function () {
    // Getting a much easier question right proves almost nothing.
    $after = $this->elo->nextRating(1600, 900, correct: true, answersCount: 0);

    expect($after - 1600)->toBeLessThanOrEqual(2);
});

it('punishes losing to a much easier question', function () {
    $after = $this->elo->nextRating(1600, 900, correct: false, answersCount: 0);

    expect(1600 - $after)->toBeGreaterThan(30);
});

it('moves an established student less than a new one', function () {
    $new = $this->elo->nextRating(1200, 1200, correct: true, answersCount: 0);
    $seasoned = $this->elo->nextRating(1200, 1200, correct: true, answersCount: 100);

    expect($new - 1200)->toBeGreaterThan($seasoned - 1200);
});

it('never falls below the floor', function () {
    $after = $this->elo->nextRating(EloRating::MIN, 2400, correct: false, answersCount: 0);

    expect($after)->toBe(EloRating::MIN);
});

it('never rises above the ceiling', function () {
    $after = $this->elo->nextRating(EloRating::MAX, 400, correct: true, answersCount: 0);

    expect($after)->toBe(EloRating::MAX);
});

it('clamps a rating from outside the range', function () {
    expect($this->elo->clamp(100))->toBe(EloRating::MIN)
        ->and($this->elo->clamp(9999))->toBe(EloRating::MAX)
        ->and($this->elo->clamp(1200))->toBe(1200);
});

it('climbs steadily through a run of correct answers', function () {
    // The exit criterion in the roadmap: answer correctly again and again and
    // the questions must get noticeably harder.
    $rating = EloRating::START;

    for ($i = 0; $i < 10; $i++) {
        $rating = $this->elo->nextRating($rating, $rating, correct: true, answersCount: $i);
    }

    expect($rating)->toBeGreaterThan(EloRating::START + 150);
});

it('falls steadily through a run of wrong answers', function () {
    $rating = EloRating::START;

    for ($i = 0; $i < 10; $i++) {
        $rating = $this->elo->nextRating($rating, $rating, correct: false, answersCount: $i);
    }

    expect($rating)->toBeLessThan(EloRating::START - 150);
});
