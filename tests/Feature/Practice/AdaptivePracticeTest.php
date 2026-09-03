<?php

use App\Actions\AnswerPracticeQuestion;
use App\Actions\EndPracticeSession;
use App\Actions\StartPracticeSession;
use App\Enums\AbilityLevel;
use App\Exceptions\PracticeException;
use App\Models\PracticeAnswer;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\StudentAbility;
use App\Models\Subject;
use App\Models\User;
use App\Services\Adaptive\EloRating;
use App\Services\Adaptive\QuestionPicker;

beforeEach(function () {
    $this->murid = User::factory()->murid()->create();
    $this->mapel = Subject::factory()->create();

    $this->start = app(StartPracticeSession::class);
    $this->answer = app(AnswerPracticeQuestion::class);
    $this->end = app(EndPracticeSession::class);
    $this->picker = app(QuestionPicker::class);
});

function soal(int $difficulty, array $attributes = []): Question
{
    return Question::factory()->published()->create(array_merge([
        'subject_id' => test()->mapel->id,
        'difficulty' => $difficulty,
    ], $attributes));
}

it('starts a student at the baseline rating', function () {
    $this->start->handle($this->murid, $this->mapel);

    expect(StudentAbility::sole()->rating)->toBe(EloRating::START)
        ->and(StudentAbility::sole()->answers_count)->toBe(0);
});

it('resumes an open session rather than starting a second', function () {
    // Otherwise closing a tab would fragment a student's history into dozens
    // of one-question sittings.
    $first = $this->start->handle($this->murid, $this->mapel);
    $second = $this->start->handle($this->murid, $this->mapel);

    expect($second->id)->toBe($first->id)
        ->and(PracticeSession::count())->toBe(1);
});

it('starts a fresh session once the previous one ended', function () {
    $first = $this->start->handle($this->murid, $this->mapel);
    $this->end->handle($first);

    expect($this->start->handle($this->murid, $this->mapel)->id)->not->toBe($first->id);
});

it('keeps a separate rating for each subject', function () {
    // A student can be strong in Matematika and weak in IPA.
    $lain = Subject::factory()->create();

    $this->start->handle($this->murid, $this->mapel);
    $this->start->handle($this->murid, $lain);

    expect(StudentAbility::where('user_id', $this->murid->id)->count())->toBe(2);
});

it('raises the rating after a correct answer', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $question = soal(1200);

    $outcome = $this->answer->handle($session, $question, $question->answer_key);

    expect($outcome->correct)->toBeTrue()
        ->and($outcome->ratingAfter)->toBeGreaterThan($outcome->ratingBefore)
        ->and(StudentAbility::sole()->rating)->toBe($outcome->ratingAfter);
});

it('lowers the rating after a wrong answer', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $question = soal(1200);

    $outcome = $this->answer->handle($session, $question, wrongOption($question));

    expect($outcome->correct)->toBeFalse()
        ->and($outcome->ratingAfter)->toBeLessThan($outcome->ratingBefore);
});

it('gives harder questions after a run of correct answers', function () {
    // The roadmap's exit criterion for M5, end to end.
    $session = $this->start->handle($this->murid, $this->mapel);

    foreach (range(1, 12) as $i) {
        soal(1000 + $i * 100, ['stem' => "Soal bertingkat nomor {$i} untuk latihan adaptif"]);
    }

    $firstTarget = $this->picker->pick($this->murid, $this->mapel->id, StudentAbility::sole()->rating);

    foreach (range(1, 8) as $i) {
        $q = soal(1200 + $i, ['stem' => "Soal benar beruntun nomor {$i} yang cukup panjang"]);
        $this->answer->handle($session, $q, $q->answer_key);
    }

    $laterTarget = $this->picker->pick($this->murid, $this->mapel->id, StudentAbility::sole()->refresh()->rating);

    expect(StudentAbility::sole()->rating)->toBeGreaterThan(EloRating::START)
        ->and($laterTarget->question->difficulty)->toBeGreaterThan($firstTarget->question->difficulty - 1);
});

it('records the rating either side of every answer', function () {
    // So a teacher can explain a level change to a parent months later.
    $session = $this->start->handle($this->murid, $this->mapel);
    $question = soal(1300);

    $this->answer->handle($session, $question, $question->answer_key);

    $answer = PracticeAnswer::sole();

    expect($answer->rating_before)->toBe(EloRating::START)
        ->and($answer->rating_after)->toBeGreaterThan($answer->rating_before)
        ->and($answer->is_correct)->toBeTrue();
});

it('counts answers on the session as they come in', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $benar = soal(1200, ['stem' => 'Soal pertama yang cukup panjang untuk lolos validasi']);
    $salah = soal(1200, ['stem' => 'Soal kedua yang cukup panjang untuk lolos validasi']);

    $this->answer->handle($session, $benar, $benar->answer_key);
    $this->answer->handle($session, $salah, wrongOption($salah));

    expect($session->refresh()->questions_count)->toBe(2)
        ->and($session->correct_count)->toBe(1);
});

it('moves the question difficulty once enough students have seen it', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $question = soal(1200, ['times_answered' => 50, 'times_correct' => 45]);

    $this->answer->handle($session, $question, $question->answer_key);

    expect($question->refresh()->difficulty)->toBeLessThan(1200)
        ->and($question->times_answered)->toBe(51)
        ->and($question->times_correct)->toBe(46);
});

it('leaves a new question difficulty alone but still counts the answer', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $question = soal(1200);

    $this->answer->handle($session, $question, $question->answer_key);

    expect($question->refresh()->difficulty)->toBe(1200)
        ->and($question->times_answered)->toBe(1);
});

it('refuses an answer to a question from another subject', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $asing = Question::factory()->published()->create();

    expect(fn () => $this->answer->handle($session, $asing, 'A'))
        ->toThrow(PracticeException::class, 'bukan dari mata pelajaran');
});

it('refuses an answer to a question that is not published', function () {
    // An unreviewed AI draft must not reach a student in practice either.
    $session = $this->start->handle($this->murid, $this->mapel);
    $draft = Question::factory()->fromAi()->review()->create(['subject_id' => $this->mapel->id]);

    expect(fn () => $this->answer->handle($session, $draft, 'A'))
        ->toThrow(PracticeException::class, 'tidak tersedia');
});

it('refuses an answer once the session is closed', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $this->end->handle($session);
    $question = soal(1200);

    expect(fn () => $this->answer->handle($session->refresh(), $question, 'A'))
        ->toThrow(PracticeException::class, 'sudah ditutup');
});

it('closes a session only once', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    $this->end->handle($session);
    $closedAt = $session->refresh()->ended_at->timestamp;

    $this->travelTo(now()->addMinutes(5));
    $this->end->handle($session);

    expect($session->refresh()->ended_at->timestamp)->toBe($closedAt);
});

it('hands the answer and explanation back for immediate feedback', function () {
    // Unlike an exam: the point of practice is finding out straight away.
    $session = $this->start->handle($this->murid, $this->mapel);
    $question = soal(1200, ['explanation' => 'Karena dua ditambah dua sama dengan empat.']);

    $outcome = $this->answer->handle($session, $question, wrongOption($question));

    expect($outcome->answerKey)->toBe($question->answer_key)
        ->and($outcome->explanation)->toBe('Karena dua ditambah dua sama dengan empat.');
});

it('reports when a level changes', function () {
    $session = $this->start->handle($this->murid, $this->mapel);
    StudentAbility::sole()->forceFill(['rating' => 1299])->save();
    $question = soal(1500);

    $outcome = $this->answer->handle($session, $question, $question->answer_key);

    expect($outcome->levelBefore())->toBe(AbilityLevel::Berkembang)
        ->and($outcome->levelAfter())->toBe(AbilityLevel::Mahir)
        ->and($outcome->levelChanged())->toBeTrue();
});

/** Any option that is not the answer key. */
function wrongOption(Question $question): string
{
    return collect(Question::OPTION_KEYS)
        ->first(fn (string $key) => $key !== $question->answer_key);
}
