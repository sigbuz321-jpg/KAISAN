<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\LeaderboardEntry;
use App\Models\Season;
use App\Models\Subject;
use App\Models\User;
use App\Services\Leaderboard\LeaderboardCalculator;

beforeEach(function () {
    $this->season = Season::factory()->active()->create();
    $this->mapel = Subject::factory()->create(['name' => 'Matematika']);
    $this->calculator = app(LeaderboardCalculator::class);
});

/** A submitted, unvoided attempt worth the given score. */
function nilai(User $student, string $score, ?Exam $exam = null, string $weight = '1.00'): ExamAttempt
{
    $exam ??= Exam::factory()->graded()->create([
        'season_id' => test()->season->id,
        'subject_id' => test()->mapel->id,
        'difficulty_weight' => $weight,
    ]);

    return ExamAttempt::factory()->submitted()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => $score,
    ]);
}

it('ranks students by their total points', function () {
    $juara = User::factory()->murid()->create(['name' => 'Juara']);
    $kedua = User::factory()->murid()->create(['name' => 'Kedua']);

    nilai($juara, '90.00');
    nilai($kedua, '70.00');

    $this->calculator->recalculate($this->season);

    $board = LeaderboardEntry::combined()->orderBy('rank')->get();

    expect($board)->toHaveCount(2)
        ->and($board[0]->user_id)->toBe($juara->id)
        ->and($board[0]->rank)->toBe(1)
        ->and($board[1]->rank)->toBe(2);
});

it('weights a harder exam more heavily', function () {
    // Without this, an easy exam and a hard one contribute the same, and
    // students learn to avoid the hard ones.
    $a = User::factory()->murid()->create();
    $b = User::factory()->murid()->create();

    nilai($a, '80.00', weight: '1.00');   // 80 points
    nilai($b, '50.00', weight: '2.00');   // 100 points

    $this->calculator->recalculate($this->season);

    $top = LeaderboardEntry::combined()->orderBy('rank')->first();

    expect($top->user_id)->toBe($b->id)
        ->and((float) $top->points)->toBe(100.0);
});

it('gives tied students the same rank and skips the next', function () {
    // What people expect, and it heads off a parent asking why two identical
    // scores were ranked differently.
    $a = User::factory()->murid()->create();
    $b = User::factory()->murid()->create();
    $c = User::factory()->murid()->create();

    nilai($a, '90.00');
    nilai($b, '90.00');
    nilai($c, '50.00');

    $this->calculator->recalculate($this->season);

    $ranks = LeaderboardEntry::combined()->orderBy('rank')->pluck('rank')->all();

    expect($ranks)->toBe([1, 1, 3]);
});

it('adds up several exams for one student', function () {
    $murid = User::factory()->murid()->create();

    nilai($murid, '60.00');
    nilai($murid, '40.00');

    $this->calculator->recalculate($this->season);

    expect((float) LeaderboardEntry::combined()->sole()->points)->toBe(100.0);
});

it('keeps a separate board for each subject', function () {
    $ipa = Subject::factory()->create(['name' => 'IPA']);
    $murid = User::factory()->murid()->create();

    nilai($murid, '80.00');
    nilai($murid, '60.00', Exam::factory()->graded()->create([
        'season_id' => $this->season->id,
        'subject_id' => $ipa->id,
    ]));

    $this->calculator->recalculate($this->season);

    expect(LeaderboardEntry::forSubject($this->mapel->id)->sole()->points)->toBe('80.00')
        ->and(LeaderboardEntry::forSubject($ipa->id)->sole()->points)->toBe('60.00')
        // And the combined board carries the sum.
        ->and((float) LeaderboardEntry::combined()->sole()->points)->toBe(140.0);
});

it('ignores an attempt that was never submitted', function () {
    $murid = User::factory()->murid()->create();

    ExamAttempt::factory()->create([
        'exam_id' => Exam::factory()->active()->create(['season_id' => $this->season->id])->id,
        'user_id' => $murid->id,
    ]);

    $this->calculator->recalculate($this->season);

    expect(LeaderboardEntry::count())->toBe(0);
});

it('ignores a voided result', function () {
    // Rule 6: the mark stays on the record but stops counting.
    $murid = User::factory()->murid()->create();

    ExamAttempt::factory()->voided()->create([
        'exam_id' => Exam::factory()->graded()->create([
            'season_id' => $this->season->id,
            'subject_id' => $this->mapel->id,
        ])->id,
        'user_id' => $murid->id,
        'score' => '95.00',
    ]);

    $this->calculator->recalculate($this->season);

    expect(LeaderboardEntry::count())->toBe(0);
});

it('ignores exams from another season', function () {
    $lama = Season::factory()->ended()->create();
    $murid = User::factory()->murid()->create();

    nilai($murid, '90.00', Exam::factory()->graded()->create([
        'season_id' => $lama->id,
        'subject_id' => $this->mapel->id,
    ]));

    $this->calculator->recalculate($this->season);

    expect(LeaderboardEntry::where('season_id', $this->season->id)->count())->toBe(0);
});

it('replaces the previous standings rather than adding to them', function () {
    $murid = User::factory()->murid()->create();
    nilai($murid, '60.00');

    $this->calculator->recalculate($this->season);
    $this->calculator->recalculate($this->season);

    expect(LeaderboardEntry::combined()->count())->toBe(1)
        ->and((float) LeaderboardEntry::combined()->sole()->points)->toBe(60.0);
});

it('picks up a mark submitted since the last run', function () {
    $murid = User::factory()->murid()->create();
    nilai($murid, '60.00');
    $this->calculator->recalculate($this->season);

    nilai($murid, '30.00');
    $this->calculator->recalculate($this->season);

    expect((float) LeaderboardEntry::combined()->sole()->points)->toBe(90.0);
});

it('writes nothing for a season nobody has sat an exam in', function () {
    expect($this->calculator->recalculate($this->season))->toBe(0)
        ->and(LeaderboardEntry::count())->toBe(0);
});

it('stamps every entry with when it was computed', function () {
    nilai(User::factory()->murid()->create(), '70.00');

    $this->calculator->recalculate($this->season);

    expect(LeaderboardEntry::combined()->sole()->computed_at)->not->toBeNull();
});
