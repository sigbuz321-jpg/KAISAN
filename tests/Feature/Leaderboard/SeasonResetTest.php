<?php

use App\Actions\ResetSeason;
use App\Jobs\RecalculateLeaderboard;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\LeaderboardEntry;
use App\Models\PracticeAnswer;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\Season;
use App\Models\StudentAbility;
use App\Models\Subject;
use App\Models\User;
use App\Services\Leaderboard\LeaderboardCalculator;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->season = Season::factory()->active()->create(['name' => 'Semester Ganjil']);
    $this->mapel = Subject::factory()->create();
    $this->murid = User::factory()->murid()->create();
    $this->reset = app(ResetSeason::class);
});

function nilaiDiSeason(Season $season, User $student, string $score): ExamAttempt
{
    return ExamAttempt::factory()->submitted()->create([
        'exam_id' => Exam::factory()->graded()->create([
            'season_id' => $season->id,
            'subject_id' => test()->mapel->id,
        ])->id,
        'user_id' => $student->id,
        'score' => $score,
    ]);
}

it('opens a new season and closes the old one', function () {
    $baru = $this->reset->handle('Semester Genap');

    expect($baru->name)->toBe('Semester Genap')
        ->and($baru->is_active)->toBeTrue()
        ->and($this->season->refresh()->is_active)->toBeFalse()
        ->and($this->season->ends_at)->not->toBeNull();
});

it('leaves the new season standings empty', function () {
    nilaiDiSeason($this->season, $this->murid, '80.00');

    $baru = $this->reset->handle('Semester Genap');

    expect(LeaderboardEntry::where('season_id', $baru->id)->count())->toBe(0);
});

it('keeps every exam result and mark', function () {
    // Rule 5 of domain-kaisan.md, and the most misunderstood one: a reset
    // clears the scoreboard, not the school's records.
    $attempt = nilaiDiSeason($this->season, $this->murid, '80.00');

    $this->reset->handle('Semester Genap');

    expect(ExamAttempt::count())->toBe(1)
        ->and($attempt->refresh()->score)->toBe('80.00')
        ->and($attempt->submitted_at)->not->toBeNull();
});

it('keeps every student adaptive rating', function () {
    $ability = StudentAbility::factory()->rated(1650, 80)->create([
        'user_id' => $this->murid->id,
        'subject_id' => $this->mapel->id,
    ]);

    $this->reset->handle('Semester Genap');

    expect($ability->refresh()->rating)->toBe(1650)
        ->and($ability->answers_count)->toBe(80);
});

it('keeps practice history', function () {
    $session = PracticeSession::factory()->create([
        'user_id' => $this->murid->id,
        'subject_id' => $this->mapel->id,
    ]);
    PracticeAnswer::create([
        'practice_session_id' => $session->id,
        'question_id' => Question::factory()->published()->create()->id,
        'selected_option' => 'A',
        'is_correct' => true,
        'rating_before' => 1200,
        'rating_after' => 1220,
        'answered_at' => now(),
    ]);

    $this->reset->handle('Semester Genap');

    expect(PracticeAnswer::count())->toBe(1);
});

it('freezes the final standings of the season it closes', function () {
    // Last season's champions stay on the record.
    nilaiDiSeason($this->season, $this->murid, '80.00');

    $this->reset->handle('Semester Genap');

    $frozen = LeaderboardEntry::where('season_id', $this->season->id)->combined()->sole();

    expect((float) $frozen->points)->toBe(80.0)
        ->and($frozen->rank)->toBe(1);
});

it('counts a mark submitted since the last scheduled run', function () {
    // The reset recalculates once more before freezing, so a result that
    // arrived in the last five minutes still counts.
    nilaiDiSeason($this->season, $this->murid, '55.00');

    $this->reset->handle('Semester Genap');

    expect(LeaderboardEntry::where('season_id', $this->season->id)->combined()->sole()->points)
        ->toBe('55.00');
});

it('allows only one active season at a time', function () {
    $this->reset->handle('Semester Genap');

    expect(Season::where('is_active', true)->count())->toBe(1)
        ->and(fn () => Season::factory()->active()->create())->toThrow(QueryException::class);
});

it('works on a fresh installation with no season yet', function () {
    Season::query()->delete();

    $baru = $this->reset->handle('Semester Pertama');

    expect($baru->is_active)->toBeTrue()
        ->and(Season::count())->toBe(1);
});

it('points new exams at the new season', function () {
    $baru = $this->reset->handle('Semester Genap');

    expect(Season::current()->id)->toBe($baru->id);
});

it('recalculates the active season on the schedule', function () {
    nilaiDiSeason($this->season, $this->murid, '75.00');

    (new RecalculateLeaderboard)->handle(app(LeaderboardCalculator::class));

    expect(LeaderboardEntry::combined()->sole()->points)->toBe('75.00');
});

it('does nothing when there is no active season', function () {
    // A fresh installation has none until the admin creates one. Not an error.
    Season::query()->update(['is_active' => false]);

    (new RecalculateLeaderboard)->handle(app(LeaderboardCalculator::class));

    expect(LeaderboardEntry::count())->toBe(0);
});
