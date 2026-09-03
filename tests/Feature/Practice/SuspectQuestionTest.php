<?php

use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->actingAs($this->guru);
});

it('flags a question almost nobody answers correctly', function () {
    // Below 15% correct after 20 tries it is more likely ambiguous or
    // mis-keyed than hard, and only a teacher can tell those apart.
    $mencurigakan = Question::factory()->published()->create([
        'stem' => 'Soal yang hampir tidak pernah dijawab benar oleh murid',
        'times_answered' => 40,
        'times_correct' => 2,
    ]);

    $wajar = Question::factory()->published()->create([
        'stem' => 'Soal sulit yang tetap masuk akal bagi sebagian murid',
        'times_answered' => 40,
        'times_correct' => 12,
    ]);

    Livewire::test(ListQuestions::class)
        ->filterTable('bermasalah', true)
        ->assertCanSeeTableRecords([$mencurigakan])
        ->assertCanNotSeeTableRecords([$wajar]);
});

it('does not flag a question too few students have tried', function () {
    $baru = Question::factory()->published()->create([
        'stem' => 'Soal baru yang belum cukup sering dikerjakan untuk dinilai',
        'times_answered' => 19,
        'times_correct' => 0,
    ]);

    Livewire::test(ListQuestions::class)
        ->filterTable('bermasalah', true)
        ->assertCanNotSeeTableRecords([$baru]);
});

it('does not flag an untouched question', function () {
    $belum = Question::factory()->published()->create([
        'stem' => 'Soal yang sama sekali belum pernah dikerjakan murid',
    ]);

    Livewire::test(ListQuestions::class)
        ->filterTable('bermasalah', true)
        ->assertCanNotSeeTableRecords([$belum]);
});

it('shows every question when the filter is off', function () {
    $a = Question::factory()->published()->create([
        'stem' => 'Soal pertama dalam daftar bank soal milik guru',
        'times_answered' => 40,
        'times_correct' => 2,
    ]);
    $b = Question::factory()->published()->create([
        'stem' => 'Soal kedua dalam daftar bank soal milik guru',
    ]);

    Livewire::test(ListQuestions::class)->assertCanSeeTableRecords([$a, $b]);
});

it('reports the correct rate on a question', function () {
    $question = Question::factory()->create(['times_answered' => 40, 'times_correct' => 10]);

    expect($question->correctRate())->toBe(0.25);
});

it('reports no rate for a question nobody has answered', function () {
    expect(Question::factory()->create()->correctRate())->toBeNull();
});
