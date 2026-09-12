<?php

use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Enums\Role;
use App\Enums\SchoolLevel;
use App\Models\AiGenerationJob;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('refuses to run in production', function () {
    // Artisan already asks for confirmation before seeding a production
    // database. This guard is the second line: it also stops `db:seed --force`,
    // which is exactly what a deploy script would use. Called directly so the
    // guard is what is under test, not Artisan's prompt.
    $this->app['env'] = 'production';

    expect(fn () => app(DatabaseSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'tidak boleh dijalankan di produksi');

    expect(User::count())->toBe(0);
});

it('creates one account of every role', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('role', Role::Admin)->count())->toBe(1)
        ->and(User::where('role', Role::Guru)->count())->toBe(2)
        ->and(User::where('role', Role::Murid)->count())->toBe(60);
});

it('puts every seeded student in a classroom', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Classroom::count())->toBe(3)
        ->and(User::where('role', Role::Murid)->whereNull('classroom_id')->count())->toBe(0);
});

it('leaves the AI questions waiting for review', function () {
    $this->seed(DatabaseSeeder::class);

    $ai = Question::where('source', QuestionSource::Ai)->get();

    // The bulk approval screen needs something to act on after a reset, and an
    // AI question must never be seeded as already published.
    expect($ai)->not->toBeEmpty()
        ->and($ai->where('status', '!=', QuestionStatus::Review))->toBeEmpty();
});

it('seeds published questions a teacher could build an exam from', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Subject::count())->toBeGreaterThanOrEqual(3)
        ->and(Question::where('status', QuestionStatus::Published)->count())->toBeGreaterThanOrEqual(6);
});

it('puts every demo question on the school level the demo student belongs to', function () {
    $this->seed(DatabaseSeeder::class);

    // Kurikulum Merdeka repeats subject names across levels. The seeder used to
    // resolve them by name alone and took whichever row was created first, so
    // the sample questions scattered and the grade 7 demo student could open
    // almost nothing. The demo content is deliberately SMP-only now.
    $levels = Subject::query()
        ->whereHas('questions')
        ->pluck('school_level')
        ->unique()
        ->values();

    expect($levels->all())->toBe([SchoolLevel::SMP]);
});

it('gives the demo student a subject they can actually practise', function () {
    $this->seed(DatabaseSeeder::class);

    $student = User::where('email', 'murid1@kaisan.test')->firstOrFail();

    $practicable = Subject::query()
        ->visibleTo($student)
        ->withPublishedQuestions()
        ->count();

    expect($practicable)->toBeGreaterThan(0);
});

it('stamps an approver on every published question', function () {
    $this->seed(DatabaseSeeder::class);

    $published = Question::where('status', QuestionStatus::Published)->get();

    expect($published->whereNull('approved_by'))->toBeEmpty();
});

it('can be run twice without duplicating anything', function () {
    $this->seed(DatabaseSeeder::class);

    $users = User::count();
    $questions = Question::count();

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe($users)
        ->and(Question::count())->toBe($questions);
});

it('leaves a finished generation record for the cost report', function () {
    $this->seed(DatabaseSeeder::class);

    expect(AiGenerationJob::count())->toBe(1)
        ->and(AiGenerationJob::sole()->savedCount())->toBe(3);
});
