<?php

use App\Actions\AnswerPracticeQuestion;
use App\Actions\StartPracticeSession;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\Adaptive\QuestionPicker;

beforeEach(function () {
    $this->murid = User::factory()->murid()->create();
    $this->mapel = Subject::factory()->create();
    $this->picker = app(QuestionPicker::class);
});

/** @param array<string, mixed> $attributes */
function bankQuestion(int $difficulty, string $stem, array $attributes = []): Question
{
    return Question::factory()->published()->create(array_merge([
        'subject_id' => test()->mapel->id,
        'difficulty' => $difficulty,
        'stem' => $stem,
    ], $attributes));
}

it('aims below the student rating so most answers are within reach', function () {
    // Target is rating - 150, which sits near a 70% chance of success:
    // challenging enough to learn from, not enough to give up.
    bankQuestion(1050, 'Soal yang berada tepat di sekitar target kesulitan murid');

    $picked = $this->picker->pick($this->murid, $this->mapel->id, 1200);

    expect($picked->found())->toBeTrue()
        ->and($picked->question->difficulty)->toBe(1050)
        ->and($picked->bankIsThin)->toBeFalse();
});

it('ignores questions far from the student level while closer ones exist', function () {
    bankQuestion(1050, 'Soal dekat dengan kemampuan murid saat ini');
    bankQuestion(2300, 'Soal yang jauh lebih sulit daripada kemampuan murid');

    foreach (range(1, 6) as $ignored) {
        expect($this->picker->pick($this->murid, $this->mapel->id, 1200)->question->difficulty)->toBe(1050);
    }
});

it('widens its search when nothing sits near the target', function () {
    // 1350 is 300 from the 1050 target: outside the first window, inside the
    // third. Better a slightly-off question than no question.
    bankQuestion(1350, 'Soal yang agak jauh dari target tetapi masih terjangkau');

    $picked = $this->picker->pick($this->murid, $this->mapel->id, 1200);

    expect($picked->found())->toBeTrue()
        ->and($picked->bankIsThin)->toBeFalse();
});

it('reaches outside every window rather than giving up, and says the bank is thin', function () {
    bankQuestion(2400, 'Soal yang sangat jauh di atas kemampuan murid saat ini');

    $picked = $this->picker->pick($this->murid, $this->mapel->id, 1200);

    expect($picked->found())->toBeTrue()
        // The warning is for the teacher, not the student.
        ->and($picked->bankIsThin)->toBeTrue();
});

it('returns nothing when the subject has no published questions', function () {
    Question::factory()->create(['subject_id' => $this->mapel->id]);

    $picked = $this->picker->pick($this->murid, $this->mapel->id, 1200);

    expect($picked->found())->toBeFalse()
        ->and($picked->bankIsThin)->toBeTrue();
});

it('never offers an unpublished question', function () {
    Question::factory()->fromAi()->review()->create(['subject_id' => $this->mapel->id, 'difficulty' => 1050]);
    Question::factory()->create(['subject_id' => $this->mapel->id, 'difficulty' => 1050]);

    expect($this->picker->pick($this->murid, $this->mapel->id, 1200)->found())->toBeFalse();
});

it('never offers a question from another subject', function () {
    Question::factory()->published()->create(['difficulty' => 1050]);

    expect($this->picker->pick($this->murid, $this->mapel->id, 1200)->found())->toBeFalse();
});

it('does not repeat a question answered recently', function () {
    $seen = bankQuestion(1050, 'Soal yang baru saja dikerjakan murid ini');
    $fresh = bankQuestion(1060, 'Soal lain yang belum pernah dikerjakan murid ini');

    $session = app(StartPracticeSession::class)->handle($this->murid, $this->mapel);
    app(AnswerPracticeQuestion::class)->handle($session, $seen, $seen->answer_key);

    foreach (range(1, 6) as $ignored) {
        expect($this->picker->pick($this->murid, $this->mapel->id, 1200)->question->id)->toBe($fresh->id);
    }
});

it('offers a question again once the cooldown has passed', function () {
    $only = bankQuestion(1050, 'Satu-satunya soal yang tersedia di mata pelajaran ini');

    $session = app(StartPracticeSession::class)->handle($this->murid, $this->mapel);
    app(AnswerPracticeQuestion::class)->handle($session, $only, $only->answer_key);

    $this->travelTo(now()->addDays(QuestionPicker::REPEAT_COOLDOWN_DAYS + 1));

    expect($this->picker->pick($this->murid, $this->mapel->id, 1200)->question->id)->toBe($only->id);
});

it('repeats rather than stranding a student who has seen everything', function () {
    // Telling a fourteen-year-old to come back in a month is worse than
    // showing them a question they have seen.
    $only = bankQuestion(1050, 'Satu-satunya soal, baru saja dikerjakan oleh murid');

    $session = app(StartPracticeSession::class)->handle($this->murid, $this->mapel);
    app(AnswerPracticeQuestion::class)->handle($session, $only, $only->answer_key);

    $picked = $this->picker->pick($this->murid, $this->mapel->id, 1200);

    expect($picked->question?->id)->toBe($only->id)
        ->and($picked->bankIsThin)->toBeTrue();
});

it('does not treat another student practice as this one history', function () {
    $seen = bankQuestion(1050, 'Soal yang dikerjakan murid lain, bukan murid ini');

    $lain = User::factory()->murid()->create();
    $session = app(StartPracticeSession::class)->handle($lain, $this->mapel);
    app(AnswerPracticeQuestion::class)->handle($session, $seen, $seen->answer_key);

    expect($this->picker->pick($this->murid, $this->mapel->id, 1200)->question->id)->toBe($seen->id);
});

it('varies which question it offers instead of always the closest', function () {
    // Always taking the nearest match makes practice feel like the same
    // handful of questions over and over.
    foreach (range(1, 8) as $i) {
        bankQuestion(1040 + $i, "Soal variasi nomor {$i} dengan panjang yang memadai");
    }

    $seen = collect(range(1, 15))
        ->map(fn () => $this->picker->pick($this->murid, $this->mapel->id, 1200)->question->id)
        ->unique();

    expect($seen->count())->toBeGreaterThan(1);
});
