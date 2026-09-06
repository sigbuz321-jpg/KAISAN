<?php

use App\Models\Classroom;
use App\Models\Question;
use App\Models\Season;
use App\Models\Subject;
use App\Models\User;

/**
 * The practice and exam screens are full-page Livewire components, and Livewire
 * hands their markup to the layout as $slot. The layout only ever printed
 * the content section, so both screens returned 200 with an empty <main> -- a
 * blank page in a browser, invisible to tests that assert only status codes.
 *
 * These assert the content actually reaches the page.
 */
it('renders the practice component inside the layout', function () {
    // No fixed name or slug: these run against a database that already
    // carries seeded subjects, and the slug is unique.
    $subject = Subject::factory()->create(['is_active' => true]);
    Question::factory()->published()->count(3)->create(['subject_id' => $subject->id]);

    $murid = User::factory()->murid()->create();

    $this->actingAs($murid)
        ->get(route('latihan.mulai', $subject))
        ->assertOk()
        ->assertSee('Levelmu')
        ->assertSee('Periksa jawaban');
});

it('renders the exam component inside the layout', function () {
    $kelas = Classroom::factory()->create();
    $murid = User::factory()->murid()->create(['classroom_id' => $kelas->id]);
    // Only one season may be active at a time, so reuse the running one.
    $season = Season::current() ?? Season::factory()->active()->create();

    $exam = examWithQuestions(3, ['season_id' => $season->id]);
    $exam->classrooms()->sync([$kelas->id]);

    $this->actingAs($murid)
        ->get(route('ujian.kerjakan', $exam->refresh()))
        ->assertOk()
        ->assertSee('Sisa waktu')
        ->assertSee('Kumpulkan ujian');
});

it('keeps the plain Blade pages rendering through the section', function () {
    $murid = User::factory()->murid()->create();

    $this->actingAs($murid)
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('Latihan tidak menambah poin peringkat');
});
