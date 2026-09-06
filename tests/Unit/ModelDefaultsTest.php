<?php

use App\Enums\AttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Models\AiGenerationJob;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\Season;
use App\Models\StudentAbility;
use App\Models\Subject;
use App\Models\Topic;

/*
|--------------------------------------------------------------------------
| Column defaults have to be mirrored on the model
|--------------------------------------------------------------------------
|
| A column default only exists in the database, so a model that has not been
| saved yet reports null for it. .claude/rules/coding-style.md records this
| biting three times before; the fourth crashed the teacher's exam form with
| "Call to a member function allowsQuestionEditing() on null", because the
| form asked a brand-new Exam for its status.
|
| One assertion per column, so a failure names the column that regressed.
|
*/

it('gives a new exam its status', function () {
    expect((new Exam)->status)->toBe(ExamStatus::Draft);
});

it('gives a new exam its shuffle settings', function () {
    expect((new Exam)->shuffle_questions)->toBeTrue()
        ->and((new Exam)->shuffle_options)->toBeTrue();
});

it('gives a new exam its difficulty weight', function () {
    expect((float) (new Exam)->difficulty_weight)->toBe(1.0);
});

it('gives a new attempt its status', function () {
    expect((new ExamAttempt)->status)->toBe(AttemptStatus::InProgress);
});

it('gives a new question its status and source', function () {
    expect((new Question)->status)->toBe(QuestionStatus::Draft)
        ->and((new Question)->source)->toBe(QuestionSource::Manual);
});

it('gives a new question its counters and difficulty', function () {
    expect((new Question)->times_answered)->toBe(0)
        ->and((new Question)->times_correct)->toBe(0)
        ->and((new Question)->difficulty)->toBe(1200);
});

it('starts a new season inactive', function () {
    expect((new Season)->is_active)->toBeFalse();
});

it('starts a new subject active', function () {
    expect((new Subject)->is_active)->toBeTrue();
});

it('gives a new topic its order', function () {
    expect((new Topic)->order)->toBe(0);
});

it('gives a new practice session its counters', function () {
    expect((new PracticeSession)->questions_count)->toBe(0)
        ->and((new PracticeSession)->correct_count)->toBe(0);
});

it('gives a new ability its rating and counter', function () {
    expect((new StudentAbility)->rating)->toBe(1200)
        ->and((new StudentAbility)->answers_count)->toBe(0);
});

it('gives a new AI job its status and counters', function () {
    expect((new AiGenerationJob)->status)->toBe(App\Enums\AiJobStatus::Queued)
        ->and((new AiGenerationJob)->prompt_tokens)->toBe(0)
        ->and((new AiGenerationJob)->completion_tokens)->toBe(0);
});
