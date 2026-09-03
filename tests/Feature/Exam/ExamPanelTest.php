<?php

use App\Actions\PickRandomQuestions;
use App\Actions\ScheduleExam;
use App\Actions\SetExamQuestions;
use App\Enums\DifficultyBand;
use App\Enums\ExamStatus;
use App\Exceptions\ExamWorkflowException;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\Exams\Pages\ExamResults;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->admin = User::factory()->admin()->create();
    $this->subject = Subject::factory()->create();

    $this->exam = Exam::factory()->create([
        'subject_id' => $this->subject->id,
        'created_by' => $this->guru->id,
        'question_count' => 0,
    ]);

    $this->set = app(SetExamQuestions::class);
    $this->pick = app(PickRandomQuestions::class);
    $this->schedule = app(ScheduleExam::class);
});

it('lets a teacher open the exam list', function () {
    $this->actingAs($this->guru)->get(ExamResource::getUrl('index'))->assertOk();
});

it('keeps students out of the exam list', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->get(ExamResource::getUrl('index'))
        ->assertForbidden();
});

it('attaches chosen questions in the order they were picked', function () {
    $questions = Question::factory()->published()->count(3)->create(['subject_id' => $this->subject->id]);
    $ids = $questions->pluck('id')->reverse()->values()->all();

    $saved = $this->set->handle($this->exam, $ids);

    expect($saved)->toBe(3)
        ->and($this->exam->questions()->pluck('questions.id')->all())->toBe($ids)
        ->and($this->exam->refresh()->question_count)->toBe(3);
});

it('refuses a question from another subject', function () {
    $mine = Question::factory()->published()->create(['subject_id' => $this->subject->id]);
    $stranger = Question::factory()->published()->create();

    $saved = $this->set->handle($this->exam, [$mine->id, $stranger->id]);

    expect($saved)->toBe(1)
        ->and($this->exam->questions()->pluck('questions.id')->all())->toBe([$mine->id]);
});

it('refuses a question that is not published', function () {
    // Rule 1 of domain-kaisan.md holds without this code knowing AI exists:
    // an unreviewed AI draft simply is not published.
    $draft = Question::factory()->create(['subject_id' => $this->subject->id]);
    $review = Question::factory()->fromAi()->review()->create(['subject_id' => $this->subject->id]);
    $live = Question::factory()->published()->create(['subject_id' => $this->subject->id]);

    $saved = $this->set->handle($this->exam, [$draft->id, $review->id, $live->id]);

    expect($saved)->toBe(1)
        ->and($this->exam->questions()->pluck('questions.id')->all())->toBe([$live->id]);
});

it('replaces the question list rather than adding to it', function () {
    $first = Question::factory()->published()->count(2)->create(['subject_id' => $this->subject->id]);
    $second = Question::factory()->published()->count(3)->create(['subject_id' => $this->subject->id]);

    $this->set->handle($this->exam, $first->pluck('id')->all());
    $this->set->handle($this->exam, $second->pluck('id')->all());

    expect($this->exam->questions()->count())->toBe(3)
        ->and($this->exam->refresh()->question_count)->toBe(3);
});

it('refuses to change the questions of an exam that has started', function () {
    $question = Question::factory()->published()->create(['subject_id' => $this->subject->id]);
    $running = Exam::factory()->active()->create(['subject_id' => $this->subject->id]);

    expect(fn () => $this->set->handle($running, [$question->id]))
        ->toThrow(ExamWorkflowException::class, 'tidak bisa diubah soalnya');
});

it('picks published questions at random', function () {
    Question::factory()->published()->count(10)->create(['subject_id' => $this->subject->id]);
    Question::factory()->count(5)->create(['subject_id' => $this->subject->id]);

    $taken = $this->pick->handle($this->exam, 6);

    expect($taken)->toBe(6)
        ->and($this->exam->questions()->where('status', 'published')->count())->toBe(6);
});

it('takes fewer questions than asked when the bank is thin', function () {
    // Reported honestly rather than silently handing over a short paper.
    Question::factory()->published()->count(3)->create(['subject_id' => $this->subject->id]);

    expect($this->pick->handle($this->exam, 10))->toBe(3);
});

it('picks only questions in the requested difficulty band', function () {
    Question::factory()->published()->count(4)->create([
        'subject_id' => $this->subject->id,
        'difficulty' => DifficultyBand::Hard->toElo(),
    ]);
    Question::factory()->published()->count(4)->create([
        'subject_id' => $this->subject->id,
        'difficulty' => DifficultyBand::Easy->toElo(),
    ]);

    $this->pick->handle($this->exam, 10, band: DifficultyBand::Hard);

    [$from, $to] = DifficultyBand::Hard->eloRange();

    expect($this->exam->questions()->count())->toBe(4)
        ->and($this->exam->questions()->whereBetween('difficulty', [$from, $to])->count())->toBe(4);
});

it('schedules a draft that has questions and classes', function () {
    $questions = Question::factory()->published()->count(3)->create(['subject_id' => $this->subject->id]);

    $this->set->handle($this->exam, $questions->pluck('id')->all());
    $this->exam->classrooms()->attach(Classroom::factory()->create()->id);
    $this->schedule->handle($this->exam);

    expect($this->exam->refresh()->status)->toBe(ExamStatus::Scheduled)
        ->and($this->exam->question_count)->toBe(3);
});

it('refuses to schedule an exam with no questions', function () {
    // Otherwise it would open, run its full duration, and grade everyone zero.
    expect(fn () => $this->schedule->handle($this->exam))
        ->toThrow(ExamWorkflowException::class, 'belum punya soal');

    expect($this->exam->refresh()->status)->toBe(ExamStatus::Draft);
});

it('refuses to schedule an exam twice', function () {
    $this->set->handle(
        $this->exam,
        Question::factory()->published()->count(2)->create(['subject_id' => $this->subject->id])->pluck('id')->all()
    );
    $this->exam->classrooms()->attach(Classroom::factory()->create()->id);

    $this->schedule->handle($this->exam);

    expect(fn () => $this->schedule->handle($this->exam->refresh()))
        ->toThrow(ExamWorkflowException::class);
});

it('shows the results page to the exam author', function () {
    $this->actingAs($this->guru)
        ->get(ExamResults::getUrl(['record' => $this->exam]))
        ->assertOk();
});

it('hides the results page from a teacher who did not create the exam', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get(ExamResults::getUrl(['record' => $this->exam]))
        ->assertForbidden();
});

it('shows the results page to the admin', function () {
    $this->actingAs($this->admin)
        ->get(ExamResults::getUrl(['record' => $this->exam]))
        ->assertOk();
});

it('keeps students off the results page', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->get(ExamResults::getUrl(['record' => $this->exam]))
        ->assertForbidden();
});

it('summarises the marks', function () {
    foreach (['90.00', '70.00', '50.00'] as $score) {
        ExamAttempt::factory()->submitted()->create([
            'exam_id' => $this->exam->id,
            'user_id' => User::factory()->murid()->create()->id,
            'score' => $score,
        ]);
    }

    $this->actingAs($this->guru);
    $page = new ExamResults;
    $page->mount($this->exam->id);

    $stats = $page->stats();

    expect($stats['peserta'])->toBe(3)
        ->and($stats['dinilai'])->toBe(3)
        ->and($stats['rata'])->toBe('70,00')
        ->and($stats['tertinggi'])->toBe('90,00')
        ->and($stats['terendah'])->toBe('50,00');
});

it('leaves a voided result out of the averages but keeps it listed', function () {
    ExamAttempt::factory()->submitted()->create([
        'exam_id' => $this->exam->id,
        'user_id' => User::factory()->murid()->create()->id,
        'score' => '80.00',
    ]);
    ExamAttempt::factory()->voided()->create([
        'exam_id' => $this->exam->id,
        'user_id' => User::factory()->murid()->create()->id,
        'score' => '20.00',
    ]);

    $this->actingAs($this->guru);
    $page = new ExamResults;
    $page->mount($this->exam->id);

    // Rule 6: a voided result stops counting but is never erased.
    expect($page->stats()['dinilai'])->toBe(1)
        ->and($page->stats()['rata'])->toBe('80,00')
        ->and($page->attempts())->toHaveCount(2);
});
