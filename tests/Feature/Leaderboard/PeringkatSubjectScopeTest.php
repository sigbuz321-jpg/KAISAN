<?php

use App\Models\Question;
use App\Models\Season;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The leaderboard offers one filter tab per subject. Those tabs must obey the
 * same two rules the practice list follows, or an SMP student is offered boards
 * for SD subjects and for subjects nobody can score in.
 */
beforeEach(function () {
    Cache::flush();

    Season::factory()->active()->create(['name' => 'Semester Ganjil']);
});

/** A subject only reaches a student once its bank has something published. */
function mapelBerisi(Subject $subject): Subject
{
    Question::factory()->published()->create(['subject_id' => $subject->id]);

    return $subject;
}

it('hides subjects from another school level on the leaderboard', function () {
    mapelBerisi(Subject::factory()->smp()->create(['name' => 'Informatika']));
    mapelBerisi(Subject::factory()->sd()->create(['name' => 'IPAS']));

    $murid = User::factory()->murid()->smp(7)->create();

    $this->actingAs($murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Informatika')
        ->assertDontSee('IPAS');
});

it('hides subjects outside the grade range on the leaderboard', function () {
    mapelBerisi(Subject::factory()->smp()->create(['name' => 'Informatika']));
    mapelBerisi(Subject::factory()->smp()->create([
        'name' => 'Prakarya Lanjut',
        'start_grade' => 9,
        'end_grade' => 9,
    ]));

    $murid = User::factory()->murid()->smp(7)->create();

    $this->actingAs($murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Informatika')
        ->assertDontSee('Prakarya Lanjut');
});

it('hides a subject whose question bank is empty from the leaderboard', function () {
    mapelBerisi(Subject::factory()->smp()->create(['name' => 'Informatika']));
    Subject::factory()->smp()->create(['name' => 'Prakarya']);

    $murid = User::factory()->murid()->smp(7)->create();

    $this->actingAs($murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Informatika')
        ->assertDontSee('Prakarya');
});

it('still offers subjects that carry no school level on the leaderboard', function () {
    mapelBerisi(Subject::factory()->create(['name' => 'Matematika', 'school_level' => null]));

    $murid = User::factory()->murid()->smp(7)->create();

    $this->actingAs($murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Matematika');
});

it('rejects a board for a subject outside the student school level', function () {
    $sd = mapelBerisi(Subject::factory()->sd()->create(['name' => 'IPAS']));

    $murid = User::factory()->murid()->smp(7)->create();

    // Falling back to the combined board is the safe answer: the student asked
    // for something they cannot see, so they get the ranking they can.
    $this->actingAs($murid)
        ->get(route('peringkat.index', ['mapel' => $sd->id]))
        ->assertOk()
        ->assertDontSee('IPAS');
});
