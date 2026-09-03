<?php

use App\Enums\AbilityLevel;
use App\Services\Adaptive\EloRating;

it('puts a brand new student in the middle, not at the top', function () {
    // Everyone starts at 1200. Telling a student on day one that they are
    // already advanced would make the label meaningless.
    expect(AbilityLevel::forRating(EloRating::START))->toBe(AbilityLevel::Berkembang);
});

it('names each band', function () {
    expect(AbilityLevel::forRating(800))->toBe(AbilityLevel::Pemula)
        ->and(AbilityLevel::forRating(1150))->toBe(AbilityLevel::Berkembang)
        ->and(AbilityLevel::forRating(1400))->toBe(AbilityLevel::Mahir)
        ->and(AbilityLevel::forRating(1800))->toBe(AbilityLevel::Ahli);
});

it('places the boundaries where they are documented', function () {
    expect(AbilityLevel::forRating(1099))->toBe(AbilityLevel::Pemula)
        ->and(AbilityLevel::forRating(1100))->toBe(AbilityLevel::Berkembang)
        ->and(AbilityLevel::forRating(1299))->toBe(AbilityLevel::Berkembang)
        ->and(AbilityLevel::forRating(1300))->toBe(AbilityLevel::Mahir)
        ->and(AbilityLevel::forRating(1549))->toBe(AbilityLevel::Mahir)
        ->and(AbilityLevel::forRating(1550))->toBe(AbilityLevel::Ahli);
});

it('covers the whole rating range', function () {
    expect(AbilityLevel::forRating(EloRating::MIN))->toBe(AbilityLevel::Pemula)
        ->and(AbilityLevel::forRating(EloRating::MAX))->toBe(AbilityLevel::Ahli);
});

it('labels every level in Indonesian', function () {
    expect(AbilityLevel::options())->toBe([
        'pemula' => 'Pemula',
        'berkembang' => 'Berkembang',
        'mahir' => 'Mahir',
        'ahli' => 'Ahli',
    ]);
});

it('describes each level in words a student can act on', function () {
    foreach (AbilityLevel::cases() as $level) {
        expect($level->description())->not->toBeEmpty()
            // Never the raw number: a visible score invites comparison.
            ->and($level->description())->not->toContain('1200');
    }
});

it('turns a rating into a progress percentage', function () {
    expect(AbilityLevel::progressFor(EloRating::MIN))->toBe(0)
        ->and(AbilityLevel::progressFor(EloRating::MAX))->toBe(100)
        ->and(AbilityLevel::progressFor(1400))->toBe(50);
});

it('keeps the progress bar inside its bounds for a stray rating', function () {
    expect(AbilityLevel::progressFor(0))->toBe(0)
        ->and(AbilityLevel::progressFor(9999))->toBe(100);
});
