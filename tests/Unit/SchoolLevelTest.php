<?php

use App\Enums\SchoolLevel;

it('includes SD, SMP, and SMA school levels', function () {
    expect(SchoolLevel::cases())->toBe([
        SchoolLevel::SD,
        SchoolLevel::SMP,
        SchoolLevel::SMA,
    ]);
});

it('provides correct labels for each school level', function () {
    expect(SchoolLevel::SD->label())->toBe('SD')
        ->and(SchoolLevel::SMP->label())->toBe('SMP')
        ->and(SchoolLevel::SMA->label())->toBe('SMA');
});

it('provides default start and end grades', function () {
    expect(SchoolLevel::SD->defaultStartGrade())->toBe(1)
        ->and(SchoolLevel::SD->defaultEndGrade())->toBe(6)
        ->and(SchoolLevel::SMP->defaultStartGrade())->toBe(7)
        ->and(SchoolLevel::SMP->defaultEndGrade())->toBe(9)
        ->and(SchoolLevel::SMA->defaultStartGrade())->toBe(10)
        ->and(SchoolLevel::SMA->defaultEndGrade())->toBe(12);
});

it('returns grades list for each level', function () {
    expect(SchoolLevel::SD->grades())->toBe([1, 2, 3, 4, 5, 6])
        ->and(SchoolLevel::SMP->grades())->toBe([7, 8, 9])
        ->and(SchoolLevel::SMA->grades())->toBe([10, 11, 12]);
});

it('derives school level from grade number', function () {
    expect(SchoolLevel::fromGrade(1))->toBe(SchoolLevel::SD)
        ->and(SchoolLevel::fromGrade(6))->toBe(SchoolLevel::SD)
        ->and(SchoolLevel::fromGrade(7))->toBe(SchoolLevel::SMP)
        ->and(SchoolLevel::fromGrade(9))->toBe(SchoolLevel::SMP)
        ->and(SchoolLevel::fromGrade(10))->toBe(SchoolLevel::SMA)
        ->and(SchoolLevel::fromGrade(12))->toBe(SchoolLevel::SMA)
        ->and(SchoolLevel::fromGrade(null))->toBeNull()
        ->and(SchoolLevel::fromGrade(13))->toBeNull()
        ->and(SchoolLevel::fromGrade(0))->toBeNull();
});

it('provides options array with Indonesian labels', function () {
    expect(SchoolLevel::options())->toBe([
        'sd' => 'SD',
        'smp' => 'SMP',
        'sma' => 'SMA',
    ]);
});
