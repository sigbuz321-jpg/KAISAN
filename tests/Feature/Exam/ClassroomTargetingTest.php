<?php

use App\Actions\ScheduleExam;
use App\Actions\SetExamClassrooms;
use App\Actions\SetExamQuestions;
use App\Exceptions\ExamWorkflowException;
use App\Filament\Resources\Exams\Pages\ExamResults;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Season;
use App\Models\User;

beforeEach(function () {
    $this->kelasA = Classroom::factory()->create(['name' => 'Kelas 7A']);
    $this->kelasB = Classroom::factory()->create(['name' => 'Kelas 7B']);

    $this->murid = User::factory()->murid()->create(['classroom_id' => $this->kelasA->id]);
    $this->muridLain = User::factory()->murid()->create(['classroom_id' => $this->kelasB->id]);

    $this->guru = User::factory()->guru()->create();
    $this->season = Season::factory()->active()->create();

    $this->setClassrooms = app(SetExamClassrooms::class);
});

function targetedExam(array $classroomIds, array $attributes = [], string $state = 'active'): Exam
{
    $exam = examWithQuestions(3, array_merge(['season_id' => test()->season->id], $attributes), state: $state);
    $exam->classrooms()->sync($classroomIds);

    return $exam->refresh();
}

it('lets a student sit an exam aimed at their class', function () {
    $exam = targetedExam([$this->kelasA->id]);

    expect($this->murid->can('start', $exam))->toBeTrue();
});

it('keeps a student out of an exam aimed at another class', function () {
    // The whole point: before this, every active student could sit every exam.
    $exam = targetedExam([$this->kelasB->id]);

    expect($this->murid->can('start', $exam))->toBeFalse()
        ->and($this->murid->can('view', $exam))->toBeFalse();
});

it('keeps a student out even with the exam address in hand', function () {
    $exam = targetedExam([$this->kelasB->id]);

    $this->actingAs($this->murid)
        ->get(route('ujian.kerjakan', $exam))
        ->assertForbidden();
});

it('lets an exam target several classes at once', function () {
    $exam = targetedExam([$this->kelasA->id, $this->kelasB->id]);

    expect($this->murid->can('start', $exam))->toBeTrue()
        ->and($this->muridLain->can('start', $exam))->toBeTrue();
});

it('keeps a student without a class out of every exam', function () {
    $exam = targetedExam([$this->kelasA->id]);
    $orphan = User::factory()->murid()->create(['classroom_id' => null]);

    expect($orphan->can('start', $exam))->toBeFalse();
});

it('lists only the exams aimed at the student class', function () {
    targetedExam([$this->kelasA->id], ['title' => 'Ujian Kelas A']);
    targetedExam([$this->kelasB->id], ['title' => 'Ujian Kelas B']);

    $this->actingAs($this->murid)
        ->get(route('ujian.index'))
        ->assertOk()
        ->assertSee('Ujian Kelas A')
        ->assertDontSee('Ujian Kelas B');
});

it('refuses to schedule an exam with no class', function () {
    $exam = Exam::factory()->create(['season_id' => $this->season->id]);
    $questions = Question::factory()->published()->count(2)->create(['subject_id' => $exam->subject_id]);
    app(SetExamQuestions::class)->handle($exam, $questions->pluck('id')->all());

    expect(fn () => app(ScheduleExam::class)->handle($exam))
        ->toThrow(ExamWorkflowException::class, 'belum punya kelas peserta');
});

it('schedules an exam that has both questions and classes', function () {
    $exam = Exam::factory()->create(['season_id' => $this->season->id]);
    $questions = Question::factory()->published()->count(2)->create(['subject_id' => $exam->subject_id]);

    app(SetExamQuestions::class)->handle($exam, $questions->pluck('id')->all());
    $this->setClassrooms->handle($exam, [$this->kelasA->id]);

    app(ScheduleExam::class)->handle($exam);

    expect($exam->refresh()->status->value)->toBe('scheduled');
});

it('refuses to change the classes of an exam already scheduled', function () {
    // Adding a class halfway through would hand those students a paper their
    // classmates started an hour earlier.
    $exam = targetedExam([$this->kelasA->id]);

    expect(fn () => $this->setClassrooms->handle($exam, [$this->kelasB->id]))
        ->toThrow(ExamWorkflowException::class, 'tidak bisa diubah setelah ujian dijadwalkan');
});

it('shows results to a teacher who takes one of the classes sitting the exam', function () {
    // This is what security.md actually asked for, and what exams.created_by
    // was only ever standing in for.
    $exam = targetedExam([$this->kelasA->id]);
    $pengampu = User::factory()->guru()->create();
    $pengampu->taughtClassrooms()->attach($this->kelasA->id);

    expect($pengampu->can('viewResults', $exam))->toBeTrue();
});

it('hides results from a teacher who takes none of those classes', function () {
    $exam = targetedExam([$this->kelasA->id]);
    $orangLain = User::factory()->guru()->create();
    $orangLain->taughtClassrooms()->attach($this->kelasB->id);

    expect($orangLain->can('viewResults', $exam))->toBeFalse();
});

it('still shows results to the teacher who wrote the exam', function () {
    // Otherwise setting an exam for a class you do not take would lock you out
    // of the marks for your own paper.
    $exam = targetedExam([$this->kelasA->id], ['created_by' => $this->guru->id]);

    expect($this->guru->taughtClassrooms()->count())->toBe(0)
        ->and($this->guru->can('viewResults', $exam))->toBeTrue();
});

it('never counts a student as teaching a class', function () {
    $this->murid->taughtClassrooms()->attach($this->kelasA->id);

    expect($this->murid->teaches($this->kelasA->id))->toBeFalse();
});

it('names the students who never opened the exam', function () {
    $exam = targetedExam([$this->kelasA->id], ['created_by' => $this->guru->id]);

    $hadir = User::factory()->murid()->create(['classroom_id' => $this->kelasA->id, 'name' => 'Murid Hadir']);
    User::factory()->murid()->create(['classroom_id' => $this->kelasA->id, 'name' => 'Murid Absen']);

    ExamAttempt::factory()->submitted()->create(['exam_id' => $exam->id, 'user_id' => $hadir->id]);

    $this->actingAs($this->guru);
    $page = new ExamResults;
    $page->mount($exam->id);

    $absent = $page->absentStudents()->pluck('name');

    expect($absent)->toContain('Murid Absen')
        ->and($absent)->not->toContain('Murid Hadir');
});

it('does not count students of other classes as absent', function () {
    $exam = targetedExam([$this->kelasA->id], ['created_by' => $this->guru->id]);

    $this->actingAs($this->guru);
    $page = new ExamResults;
    $page->mount($exam->id);

    expect($page->absentStudents()->pluck('id')->all())
        ->toContain($this->murid->id)
        ->not->toContain($this->muridLain->id);
});

it('does not chase a deactivated student for an exam', function () {
    $exam = targetedExam([$this->kelasA->id], ['created_by' => $this->guru->id]);
    $nonaktif = User::factory()->murid()->inactive()->create(['classroom_id' => $this->kelasA->id]);

    $this->actingAs($this->guru);
    $page = new ExamResults;
    $page->mount($exam->id);

    expect($page->absentStudents()->pluck('id')->all())->not->toContain($nonaktif->id);
});
