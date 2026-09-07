<?php

use App\Enums\SchoolLevel;
use App\Models\Subject;

it('generates slug with school level suffix', function () {
    $subject = Subject::create([
        'name' => 'Bahasa Indonesia',
        'school_level' => SchoolLevel::SMP,
    ]);

    expect($subject->slug)->toBe('bahasa-indonesia-smp');
});

it('returns plain name via nameOnly', function () {
    $sd = new Subject(['name' => 'Matematika', 'school_level' => SchoolLevel::SD]);
    $smp = new Subject(['name' => 'Informatika', 'school_level' => SchoolLevel::SMP]);

    expect($sd->nameOnly())->toBe('Matematika')
        ->and($smp->nameOnly())->toBe('Informatika');
});

it('formats grade range label correctly', function () {
    $none = new Subject(['name' => 'Umum']);
    $range = new Subject(['name' => 'SD', 'start_grade' => 1, 'end_grade' => 6]);
    $single = new Subject(['name' => 'Kelas 7', 'start_grade' => 7, 'end_grade' => 7]);
    $startOnly = new Subject(['name' => 'Lanjut', 'start_grade' => 7]);

    expect($none->gradeRangeLabel())->toBeNull()
        ->and($range->gradeRangeLabel())->toBe('Kelas 1–6')
        ->and($single->gradeRangeLabel())->toBe('Kelas 7')
        ->and($startOnly->gradeRangeLabel())->toBe('Mulai Kelas 7');
});

it('filters subjects by school level scope', function () {
    Subject::create(['name' => 'IPA SD', 'school_level' => SchoolLevel::SD]);
    Subject::create(['name' => 'IPA SMP', 'school_level' => SchoolLevel::SMP]);

    expect(Subject::forSchoolLevel(SchoolLevel::SD)->count())->toBe(1)
        ->and(Subject::forSchoolLevel(SchoolLevel::SMP)->count())->toBe(1);
});

it('filters subjects by grade scope', function () {
    Subject::create(['name' => 'SD Mapel', 'start_grade' => 1, 'end_grade' => 6]);
    Subject::create(['name' => 'SMP Mapel', 'start_grade' => 7, 'end_grade' => 9]);
    Subject::create(['name' => 'Umum']);

    // Grade 4 should match SD Mapel and Umum
    expect(Subject::forGrade(4)->count())->toBe(2)
        // Grade 8 should match SMP Mapel and Umum
        ->and(Subject::forGrade(8)->count())->toBe(2);
});
