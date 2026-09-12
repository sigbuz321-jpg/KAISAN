<?php

use App\Livewire\Murid\LatihanAdaptif;
use App\Models\PracticeAnswer;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\StudentAbility;
use App\Models\Subject;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->murid = User::factory()->murid()->create();
    $this->mapel = Subject::factory()->create(['name' => 'Matematika']);
});

/** @param array<string, mixed> $attributes */
function latihanSoal(array $attributes = []): Question
{
    return Question::factory()->published()->create(array_merge([
        'subject_id' => test()->mapel->id,
        'difficulty' => 1050,
        'explanation' => 'Karena hasil penjumlahannya memang demikian.',
    ], $attributes));
}

it('opens practice and offers a question', function () {
    latihanSoal();

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $component->assertOk();

    expect($component->get('soal'))->not->toBeNull()
        ->and(PracticeSession::count())->toBe(1)
        ->and(StudentAbility::count())->toBe(1);
});

it('never sends the answer key before the student has answered', function () {
    // Everything in component state is serialised into the page.
    $question = latihanSoal(['explanation' => 'RAHASIA PEMBAHASAN LATIHAN']);

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    expect(array_keys($component->get('soal')))->toBe(['id', 'stem', 'options'])
        ->and($component->html())->not->toContain('RAHASIA PEMBAHASAN LATIHAN')
        ->and($question->answer_key)->not->toBeEmpty();
});

it('shows the key and the explanation after answering', function () {
    // The whole difference from an exam: you find out while you still
    // remember why you chose it.
    latihanSoal(['explanation' => 'Karena dua ditambah dua sama dengan empat.']);

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $question = Question::find($component->get('soal')['id']);

    $component->set('pilihan', $question->answer_key)->call('jawab');

    expect($component->get('umpanBalik')['benar'])->toBeTrue()
        ->and($component->get('umpanBalik')['kunci'])->toBe($question->answer_key)
        ->and($component->get('umpanBalik')['pembahasan'])
        ->toBe('Karena dua ditambah dua sama dengan empat.');
});

it('shows incorrect feedback banner when student picks wrong option', function () {
    $question = latihanSoal(['explanation' => 'Pembahasan soal salah.']);

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $wrong = $question->answer_key === 'A' ? 'B' : 'A';
    $component->set('pilihan', $wrong)->call('jawab');

    expect($component->get('umpanBalik')['benar'])->toBeFalse()
        ->and($component->get('umpanBalik')['kunci'])->toBe($question->answer_key)
        ->and($component->html())->toContain('Belum tepat.')
        ->and($component->html())->toContain('Jawaban yang benar: '.$question->answer_key);
});

it('records the answer and moves the rating', function () {
    latihanSoal();

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $question = Question::find($component->get('soal')['id']);
    $component->set('pilihan', $question->answer_key)->call('jawab');

    expect(PracticeAnswer::count())->toBe(1)
        ->and(StudentAbility::sole()->rating)->toBeGreaterThan(StudentAbility::startingRating())
        ->and($component->get('dijawab'))->toBe(1)
        ->and($component->get('benar'))->toBe(1);
});

it('refuses to record a second answer to the same question', function () {
    latihanSoal();

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $question = Question::find($component->get('soal')['id']);

    $component->set('pilihan', $question->answer_key)->call('jawab')->call('jawab');

    expect(PracticeAnswer::count())->toBe(1);
});

it('moves on to another question', function () {
    latihanSoal(['stem' => 'Soal pertama yang cukup panjang untuk lolos validasi']);
    latihanSoal(['stem' => 'Soal kedua yang juga cukup panjang untuk lolos validasi']);

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $first = $component->get('soal')['id'];
    $question = Question::find($first);

    $component->set('pilihan', $question->answer_key)->call('jawab')->call('berikutnya');

    expect($component->get('umpanBalik'))->toBeNull()
        ->and($component->get('pilihan'))->toBeNull()
        // Already answered, so it should not come round again.
        ->and($component->get('soal')['id'])->not->toBe($first);
});

it('ends the sitting when the student is done', function () {
    latihanSoal();

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $component->call('akhiri')->assertSet('selesai', true);

    expect(PracticeSession::sole()->ended_at)->not->toBeNull();
});

it('says so plainly when the subject has no questions', function () {
    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    $component->assertOk()->assertSet('habis', true);

    expect($component->get('soal'))->toBeNull();
});

it('shows a level rather than a number', function () {
    latihanSoal();

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    // A visible score invites children to compare themselves with each other.
    expect($component->get('level'))->toBe('Berkembang')
        ->and($component->html())->not->toContain('1200');
});

it('keeps a teacher out of the practice screen', function () {
    latihanSoal();

    Livewire::actingAs(User::factory()->guru()->create())
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel])
        ->assertForbidden();
});

it('keeps a deactivated student out of the practice screen', function () {
    latihanSoal();

    Livewire::actingAs(User::factory()->murid()->inactive()->create())
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel])
        ->assertForbidden();
});

it('lists the subjects a student can practise', function () {
    latihanSoal();

    $this->actingAs($this->murid)
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('Matematika');
});

it('leaves out a subject whose question bank is empty', function () {
    latihanSoal();
    Subject::factory()->create(['name' => 'IPA Tanpa Soal']);

    // These used to be listed with a "belum ada soal" note. Kurikulum Merdeka
    // defines around eleven subjects per level and a bimbel teaches a few, so
    // the note buried the subjects a student could actually open.
    $this->actingAs($this->murid)
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('Matematika')
        ->assertDontSee('IPA Tanpa Soal');
});

it('shows a student their level on the subject list', function () {
    latihanSoal();
    StudentAbility::factory()->rated(1600, 40)->create([
        'user_id' => $this->murid->id,
        'subject_id' => $this->mapel->id,
    ]);

    $this->actingAs($this->murid)
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('Ahli')
        ->assertDontSee('1600');
});

it('keeps staff off the practice list', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get(route('latihan.index'))
        ->assertForbidden();
});

it('sends a guest to the sign-in page', function () {
    $this->get(route('latihan.index'))->assertRedirect(route('masuk'));
});

/*
|--------------------------------------------------------------------------
| Regression: the answer could be chosen but never registered
|--------------------------------------------------------------------------
|
| The radio is visually hidden and the selected styling is rendered from
| server state, so a deferred wire:model deadlocked the screen: the choice
| never reached the server, nothing re-rendered, the option never looked
| selected, and "Periksa jawaban" stayed disabled forever.
|
*/

it('sends the chosen option to the server as soon as it is picked', function () {
    latihanSoal();

    $html = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel])
        ->html();

    // Deferred binding leaves the student unable to submit at all, because the
    // only control that would flush it is the button this value enables.
    expect($html)->toContain('wire:model.live="pilihan"');
});

it('enables the check button once an option is chosen', function () {
    latihanSoal();

    $component = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel]);

    expect(tombolNonaktif($component->html(), 'jawab'))->toBeTrue();

    $component->set('pilihan', 'A');

    expect(tombolNonaktif($component->html(), 'jawab'))->toBeFalse();
});

it('marks the chosen option as checked in the markup', function () {
    latihanSoal();

    $html = Livewire::actingAs($this->murid)
        ->test(LatihanAdaptif::class, ['subject' => $this->mapel])
        ->set('pilihan', 'B')
        ->html();

    // A student has to be able to see which option they picked.
    expect(tagOpsi($html, 'B'))->toContain('checked')
        ->and(tagOpsi($html, 'A'))->not->toContain('checked');
});

/**
 * Whether the button behind a wire:click carries the boolean disabled attribute.
 *
 * Not a substring check: the class list contains disabled:opacity-70 and
 * friends, so "disabled" appears on the tag whatever its actual state.
 */
function tombolNonaktif(string $html, string $action): bool
{
    preg_match('/<button[^>]*wire:click="'.$action.'"[^>]*>/s', $html, $m);

    return (bool) preg_match('/\sdisabled(\s|>|=)/', $m[0] ?? '');
}

/** The radio input for one option letter. */
function tagOpsi(string $html, string $letter): string
{
    preg_match('/<input[^>]*value="'.$letter.'"[^>]*>/s', $html, $m);

    return $m[0] ?? '';
}
