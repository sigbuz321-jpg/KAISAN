<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\QueryException;

it('allows only one active season', function () {
    // The leaderboard has to know which season "now" means, so the database
    // refuses a second active one rather than trusting every code path.
    Season::factory()->active()->create();

    expect(fn () => Season::factory()->active()->create())
        ->toThrow(QueryException::class);
});

it('allows many finished seasons', function () {
    Season::factory()->ended()->count(3)->create();
    Season::factory()->active()->create();

    expect(Season::count())->toBe(4);
});

it('refuses an exam that ends before it starts', function () {
    expect(fn () => Exam::factory()->create([
        'starts_at' => now()->addHours(2),
        'ends_at' => now()->addHour(),
    ]))->toThrow(QueryException::class);
});

it('refuses a difficulty weight outside one to two', function () {
    expect(fn () => Exam::factory()->create(['difficulty_weight' => '2.50']))
        ->toThrow(QueryException::class);

    expect(fn () => Exam::factory()->create(['difficulty_weight' => '0.50']))
        ->toThrow(QueryException::class);
});

it('refuses an exam with no duration', function () {
    expect(fn () => Exam::factory()->create(['duration_minutes' => 0]))
        ->toThrow(QueryException::class);
});

it('refuses a second attempt at the same exam by the same student', function () {
    // Rule 2 of domain-kaisan.md, enforced by the database so two browser tabs
    // cannot race past it.
    $exam = Exam::factory()->active()->create();
    $murid = User::factory()->murid()->create();

    ExamAttempt::factory()->create(['exam_id' => $exam->id, 'user_id' => $murid->id]);

    expect(fn () => ExamAttempt::factory()->create(['exam_id' => $exam->id, 'user_id' => $murid->id]))
        ->toThrow(QueryException::class);
});

it('lets the same student sit different exams', function () {
    $murid = User::factory()->murid()->create();

    ExamAttempt::factory()->count(2)->sequence(
        ['exam_id' => Exam::factory()->active()->create()->id],
        ['exam_id' => Exam::factory()->active()->create()->id],
    )->create(['user_id' => $murid->id]);

    expect(ExamAttempt::where('user_id', $murid->id)->count())->toBe(2);
});

it('refuses a score outside zero to one hundred', function () {
    $attempt = ExamAttempt::factory()->create();

    expect(fn () => $attempt->forceFill(['score' => '150.00'])->save())
        ->toThrow(QueryException::class);
});

it('refuses to void a result without saying why', function () {
    // Rule 6: the bimbel must always be able to explain a record to a parent.
    $attempt = ExamAttempt::factory()->submitted()->create();

    expect(fn () => $attempt->forceFill(['voided_at' => now()])->save())
        ->toThrow(QueryException::class);
});

it('keeps the score on a voided result', function () {
    $attempt = ExamAttempt::factory()->voided()->create();

    // Voiding stops it counting; it never erases what happened.
    expect($attempt->score)->not->toBeNull()
        ->and($attempt->countsTowardsRanking())->toBeFalse();
});

it('refuses a chosen option outside A to D', function () {
    $attempt = ExamAttempt::factory()->create();

    expect(fn () => $attempt->answers()->create([
        'question_id' => App\Models\Question::factory()->create()->id,
        'selected_option' => 'Z',
        'answered_at' => now(),
    ]))->toThrow(QueryException::class);
});
