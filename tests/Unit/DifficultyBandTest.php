<?php

use App\Enums\DifficultyBand;

it('places each band on the Elo scale around the 1200 baseline', function () {
    expect(DifficultyBand::Easy->toElo())->toBe(1000)
        ->and(DifficultyBand::Medium->toElo())->toBe(1200)
        ->and(DifficultyBand::Hard->toElo())->toBe(1400);
});

it('orders the bands from easiest to hardest', function () {
    expect(DifficultyBand::Easy->toElo())->toBeLessThan(DifficultyBand::Medium->toElo())
        ->and(DifficultyBand::Medium->toElo())->toBeLessThan(DifficultyBand::Hard->toElo());
});

it('labels every band in Indonesian', function () {
    expect(DifficultyBand::options())->toBe([
        'easy' => 'Mudah',
        'medium' => 'Sedang',
        'hard' => 'Sulit',
    ]);
});

it('sends the router the English band word', function () {
    expect(DifficultyBand::Hard->promptWord())->toBe('hard');
});
