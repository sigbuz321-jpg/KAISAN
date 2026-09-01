<?php

use App\Models\Question;
use App\Models\Subject;
use App\Models\User;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
});

it('lets a teacher open the question bank', function () {
    $this->actingAs($this->guru)->get('/admin/questions')->assertOk();
});

it('keeps students out of the question bank', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->get('/admin/questions')
        ->assertForbidden();
});

it('lets a teacher see subjects but only an admin create them', function () {
    $subject = Subject::factory()->create();

    expect($this->guru->can('viewAny', Subject::class))->toBeTrue()
        ->and($this->guru->can('create', Subject::class))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('update', $subject))->toBeTrue();
});

it('allows deleting a draft but never a published question', function () {
    $draft = Question::factory()->create();
    $published = Question::factory()->published()->create();

    expect($this->guru->can('delete', $draft))->toBeTrue()
        ->and($this->guru->can('delete', $published))->toBeFalse();
});

it('shows the question wording in the list', function () {
    Question::factory()->create(['stem' => 'Siapa penulis proklamasi?']);

    $this->actingAs($this->guru)
        ->get('/admin/questions')
        ->assertOk()
        ->assertSee('Siapa penulis proklamasi?');
});

it('renders the student preview without leaking the answer key', function () {
    $question = Question::factory()->create([
        'stem' => 'Ibu kota Jawa Barat?',
        'options' => ['A' => 'Bandung', 'B' => 'Semarang', 'C' => 'Surabaya', 'D' => 'Medan'],
        'answer_key' => 'A',
        'explanation' => 'Bandung adalah ibu kota Jawa Barat.',
    ]);

    $studentView = view('components.question-preview', [
        'question' => $question,
        'showAnswer' => false,
    ])->render();

    expect($studentView)->toContain('Ibu kota Jawa Barat?')
        ->and($studentView)->toContain('Bandung')
        ->and($studentView)->not->toContain('Kunci jawaban')
        ->and($studentView)->not->toContain('Bandung adalah ibu kota Jawa Barat.');
});

it('shows the answer key to a teacher previewing the question', function () {
    $question = Question::factory()->create(['answer_key' => 'A']);

    $teacherView = view('components.question-preview', [
        'question' => $question,
        'showAnswer' => true,
    ])->render();

    expect($teacherView)->toContain('Kunci jawaban');
});
