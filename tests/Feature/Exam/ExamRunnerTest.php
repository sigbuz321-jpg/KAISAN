<?php

use App\Enums\AttemptStatus;
use App\Livewire\Murid\PengerjaanUjian;
use App\Models\ExamAttempt;
use App\Models\Season;
use App\Models\User;
use App\Services\Exams\ExamPaper;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(now()->startOfMinute());
    $this->murid = User::factory()->murid()->create();
    $this->paper = app(ExamPaper::class);
});

it('starts an attempt when a student opens the exam', function () {
    $exam = examWithQuestions(4);

    Livewire::actingAs($this->murid)
        ->test(PengerjaanUjian::class, ['exam' => $exam])
        ->assertOk();

    $attempt = ExamAttempt::sole();

    expect($attempt->user_id)->toBe($this->murid->id)
        ->and($attempt->status)->toBe(AttemptStatus::InProgress);
});

it('never sends the answer key to the browser', function () {
    // The single most important assertion in M4. Everything the component
    // holds is serialised into the page for Livewire.
    $exam = examWithQuestions(4);
    $exam->questions()->first()->update(['explanation' => 'RAHASIA PEMBAHASAN']);

    $rendered = Livewire::actingAs($this->murid)
        ->test(PengerjaanUjian::class, ['exam' => $exam])
        ->html();

    $keys = array_values(answerKeysOf($exam));

    expect($rendered)->not->toContain('answer_key')
        ->and($rendered)->not->toContain('RAHASIA PEMBAHASAN');

    // And the paper itself carries only the four safe fields.
    foreach ($this->paper->forStudent(ExamAttempt::sole()) as $question) {
        expect(array_keys($question))->toBe(['id', 'number', 'stem', 'options']);
    }

    expect($keys)->not->toBeEmpty();
});

it('saves an answer as soon as it is chosen', function () {
    $exam = examWithQuestions(3, ['shuffle_options' => false]);

    $component = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);
    $questionId = $component->get('paper')[0]['id'];

    $component->set("answers.{$questionId}", 'B');

    expect(ExamAttempt::sole()->answers()->sole())
        ->question_id->toBe($questionId)
        ->selected_option->toBe('B')
        // Still ungraded: marking now would put the key in the student's row.
        ->is_correct->toBeNull();
});

it('brings back answers a student gave before losing their connection', function () {
    $exam = examWithQuestions(4);

    $first = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);
    $questionId = $first->get('paper')[0]['id'];
    $first->set("answers.{$questionId}", 'C');

    // A fresh mount stands in for the reload after the connection came back.
    $second = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);

    expect($second->get('answers')[$questionId])->toBe('C');
});

it('shows the same letter the student picked on a shuffled paper', function () {
    $exam = examWithQuestions(4, ['shuffle_options' => true]);

    $first = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);
    $questionId = $first->get('paper')[0]['id'];
    $first->set("answers.{$questionId}", 'D');

    $second = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);

    // Stored in the question's own lettering, shown back in the student's.
    expect($second->get('answers')[$questionId])->toBe('D');
});

it('does not query the database again when moving between questions', function () {
    // .claude/rules/performance.md: the paper is loaded once and kept in
    // component state. Re-querying per question is what falls over at 150
    // students.
    $exam = examWithQuestions(10);

    $component = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $component->call('ke', 5)->call('ke', 9)->call('ke', 2);

    expect(count(DB::getQueryLog()))->toBe(0);

    DB::disableQueryLog();
});

it('scores the paper on the server when the student hands it in', function () {
    $exam = examWithQuestions(4, ['shuffle_options' => false, 'shuffle_questions' => false]);

    $component = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);

    foreach (answerKeysOf($exam) as $questionId => $key) {
        $component->set("answers.{$questionId}", $key);
    }

    $component->call('kumpulkan')
        ->assertSet('selesai', true)
        ->assertSet('skor', '100.00');

    expect(ExamAttempt::sole()->status)->toBe(AttemptStatus::Submitted);
});

it('counts questions left blank as wrong', function () {
    $exam = examWithQuestions(4, ['shuffle_options' => false, 'shuffle_questions' => false]);

    $component = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);
    $keys = answerKeysOf($exam);
    $first = array_key_first($keys);

    $component->set("answers.{$first}", $keys[$first])->call('kumpulkan');

    expect($component->get('skor'))->toBe('25.00');
});

it('refuses a late submission and says so plainly', function () {
    $exam = examWithQuestions(2, ['duration_minutes' => 30]);

    $component = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);

    $this->travelTo(now()->addMinutes(31));

    $component->call('kumpulkan')
        ->assertSet('skor', null)
        ->assertSet('selesai', true);

    expect($component->get('pesan'))->toContain('Waktu ujian sudah habis')
        ->and(ExamAttempt::sole()->submitted_at)->toBeNull();
});

it('refuses to save an answer after the time is up', function () {
    $exam = examWithQuestions(3, ['duration_minutes' => 30]);

    $component = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);
    $questionId = $component->get('paper')[0]['id'];

    $this->travelTo(now()->addMinutes(31));
    $component->set("answers.{$questionId}", 'A');

    expect(ExamAttempt::sole()->answers()->count())->toBe(0)
        ->and($component->get('pesan'))->toContain('Waktu ujian sudah habis');
});

it('cannot be handed in twice', function () {
    $exam = examWithQuestions(2, ['shuffle_options' => false, 'shuffle_questions' => false]);

    $component = Livewire::actingAs($this->murid)->test(PengerjaanUjian::class, ['exam' => $exam]);
    $component->call('kumpulkan');
    $submittedAt = ExamAttempt::sole()->submitted_at->timestamp;

    $component->call('kumpulkan');

    expect(ExamAttempt::sole()->submitted_at->timestamp)->toBe($submittedAt);
});

it('keeps a teacher out of the exam screen', function () {
    $exam = examWithQuestions(2);

    Livewire::actingAs(User::factory()->guru()->create())
        ->test(PengerjaanUjian::class, ['exam' => $exam])
        ->assertForbidden();
});

it('keeps a deactivated student out of the exam screen', function () {
    $exam = examWithQuestions(2);

    Livewire::actingAs(User::factory()->murid()->inactive()->create())
        ->test(PengerjaanUjian::class, ['exam' => $exam])
        ->assertForbidden();
});

it('refuses to open an exam that has not started', function () {
    $exam = examWithQuestions(2, state: 'scheduled');

    Livewire::actingAs($this->murid)
        ->test(PengerjaanUjian::class, ['exam' => $exam])
        ->assertForbidden();
});

it('lists the exams a student can sit', function () {
    $season = Season::factory()->active()->create();
    $open = examWithQuestions(2, ['season_id' => $season->id, 'title' => 'Ulangan Aljabar']);
    $draft = examWithQuestions(2, ['season_id' => $season->id, 'title' => 'Belum Siap'], state: 'draft');

    $this->actingAs($this->murid)
        ->get(route('ujian.index'))
        ->assertOk()
        ->assertSee('Ulangan Aljabar')
        ->assertDontSee('Belum Siap');

    expect($open->id)->not->toBe($draft->id);
});

it('keeps staff off the student exam list', function () {
    Season::factory()->active()->create();

    $this->actingAs(User::factory()->guru()->create())
        ->get(route('ujian.index'))
        ->assertForbidden();
});

it('shows a student their own score once it is in', function () {
    $season = Season::factory()->active()->create();
    $exam = examWithQuestions(2, ['season_id' => $season->id], state: 'closed');

    ExamAttempt::factory()->submitted()->create([
        'exam_id' => $exam->id,
        'user_id' => $this->murid->id,
        'score' => '75.00',
    ]);

    $this->actingAs($this->murid)
        ->get(route('ujian.index'))
        ->assertOk()
        ->assertSee('75');
});

it('does not show a student anyone else score', function () {
    $season = Season::factory()->active()->create();
    $exam = examWithQuestions(2, ['season_id' => $season->id], state: 'closed');

    ExamAttempt::factory()->submitted()->create([
        'exam_id' => $exam->id,
        'user_id' => User::factory()->murid()->create()->id,
        'score' => '93.00',
    ]);

    $this->actingAs($this->murid)
        ->get(route('ujian.index'))
        ->assertOk()
        ->assertDontSee('93');
});
