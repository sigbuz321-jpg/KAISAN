<?php

use App\Services\AiRouter\GeneratedQuestionValidator;

beforeEach(function () {
    $this->validator = new GeneratedQuestionValidator;
});

it('accepts a well-formed candidate', function () {
    $result = $this->validator->validate([aiQuestion()]);

    expect($result->acceptedCount())->toBe(1)
        ->and($result->rejectedCount())->toBe(0);
});

it('squishes stray whitespace out of the stem and the options', function () {
    $result = $this->validator->validate([
        aiQuestion('  Berapakah   hasil dari 2 tambah 2?  ', [
            'options' => ['A' => ' empat ', 'B' => 'tiga', 'C' => 'lima', 'D' => 'enam'],
        ]),
    ]);

    expect($result->accepted[0]['stem'])->toBe('Berapakah hasil dari 2 tambah 2?')
        ->and($result->accepted[0]['options']['A'])->toBe('empat');
});

it('rejects a stem too short to be a real question', function () {
    $result = $this->validator->validate([aiQuestion('Apa?')]);

    expect($result->acceptedCount())->toBe(0)
        ->and($result->rejectedCount())->toBe(1);
});

it('rejects a candidate with a missing explanation', function () {
    $result = $this->validator->validate([aiQuestion(overrides: ['explanation' => ''])]);

    expect($result->acceptedCount())->toBe(0);
});

it('rejects a candidate with an empty option', function () {
    $result = $this->validator->validate([
        aiQuestion(overrides: ['options' => ['A' => 'empat', 'B' => '', 'C' => 'lima', 'D' => 'enam']]),
    ]);

    expect($result->acceptedCount())->toBe(0)
        ->and($result->reasons[0])->toContain('kosong');
});

it('rejects two candidates with the same stem inside one batch', function () {
    $result = $this->validator->validate([
        aiQuestion('Berapakah hasil dari 2 tambah 2?'),
        aiQuestion('berapakah   HASIL dari 2 tambah 2?'),
    ]);

    expect($result->acceptedCount())->toBe(1)
        ->and($result->reasons[0])->toContain('sama dengan soal lain');
});

it('numbers each rejection so a teacher can tell which one failed', function () {
    $result = $this->validator->validate([
        aiQuestion('Berapakah hasil dari 2 tambah 2?'),
        aiQuestion('Soal kedua yang kuncinya salah', ['answer_key' => 'Z']),
    ]);

    expect($result->reasons[0])->toStartWith('Soal ke-2:');
});

it('writes every rejection reason in Indonesian', function () {
    $result = $this->validator->validate([aiQuestion('Apa?')]);

    expect($result->reasons[0])->not->toContain('The ')
        ->and($result->reasons[0])->not->toContain('field');
});
