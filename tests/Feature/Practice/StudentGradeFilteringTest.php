<?php

use App\Enums\SchoolLevel;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;

beforeEach(function () {
    $this->sdSubject = Subject::factory()->create([
        'name' => 'Matematika SD',
        'school_level' => SchoolLevel::SD,
        'start_grade' => 1,
        'end_grade' => 6,
    ]);

    $this->smpSubject = Subject::factory()->create([
        'name' => 'Matematika SMP',
        'school_level' => SchoolLevel::SMP,
        'start_grade' => 7,
        'end_grade' => 9,
    ]);

    $this->smaSubject = Subject::factory()->create([
        'name' => 'Matematika SMA',
        'school_level' => SchoolLevel::SMA,
        'start_grade' => 10,
        'end_grade' => 12,
    ]);

    $this->generalSubject = Subject::factory()->create([
        'name' => 'Pendidikan Karakter',
        'school_level' => null,
        'start_grade' => null,
        'end_grade' => null,
    ]);

    foreach ([$this->sdSubject, $this->smpSubject, $this->smaSubject, $this->generalSubject] as $subj) {
        Question::factory()->published()->create(['subject_id' => $subj->id]);
    }
});

it('shows only SD subjects to an SD student', function () {
    $student = User::factory()->murid()->create([
        'school_level' => SchoolLevel::SD,
        'grade' => 4,
    ]);

    $this->actingAs($student)
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('Matematika SD')
        ->assertSee('Pendidikan Karakter')
        ->assertDontSee('Matematika SMP')
        ->assertDontSee('Matematika SMA');
});

it('shows only SMP subjects to an SMP student', function () {
    $student = User::factory()->murid()->create([
        'school_level' => SchoolLevel::SMP,
        'grade' => 8,
    ]);

    $this->actingAs($student)
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('Matematika SMP')
        ->assertSee('Pendidikan Karakter')
        ->assertDontSee('Matematika SD')
        ->assertDontSee('Matematika SMA');
});

it('shows only SMA subjects to an SMA student', function () {
    $student = User::factory()->murid()->create([
        'school_level' => SchoolLevel::SMA,
        'grade' => 11,
    ]);

    $this->actingAs($student)
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('Matematika SMA')
        ->assertSee('Pendidikan Karakter')
        ->assertDontSee('Matematika SD')
        ->assertDontSee('Matematika SMP');
});

it('refuses an SD student from opening SMP practice session', function () {
    $student = User::factory()->murid()->create([
        'school_level' => SchoolLevel::SD,
        'grade' => 4,
    ]);

    $this->actingAs($student)
        ->get(route('latihan.mulai', $this->smpSubject))
        ->assertForbidden();
});

it('refuses an SMP student from opening SMA practice session', function () {
    $student = User::factory()->murid()->create([
        'school_level' => SchoolLevel::SMP,
        'grade' => 8,
    ]);

    $this->actingAs($student)
        ->get(route('latihan.mulai', $this->smaSubject))
        ->assertForbidden();
});

it('allows student to open practice session for their own level', function () {
    $student = User::factory()->murid()->create([
        'school_level' => SchoolLevel::SMP,
        'grade' => 8,
    ]);

    $this->actingAs($student)
        ->get(route('latihan.mulai', $this->smpSubject))
        ->assertOk();
});
