<?php

use App\Actions\SaveAttemptAnswer;
use App\Actions\StartExamAttempt;
use App\Actions\SubmitExamAttempt;
use App\Enums\AttemptStatus;
use App\Exceptions\ExamWorkflowException;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $this->travelTo(now()->startOfMinute());

    $this->murid = User::factory()->murid()->create();
    $this->start = app(StartExamAttempt::class);
    $this->save = app(SaveAttemptAnswer::class);
    $this->submit = app(SubmitExamAttempt::class);
});

it('opens an attempt stamped with the server clock', function () {
    $exam = examWithQuestions(4);

    $attempt = $this->start->handle($exam, $this->murid);

    expect($attempt->status)->toBe(AttemptStatus::InProgress)
        ->and($attempt->total_questions)->toBe(4)
        ->and($attempt->started_at->timestamp)->toBe(now()->timestamp)
        ->and($attempt->submitted_at)->toBeNull();
});

it('refuses to open an exam that is not running', function () {
    $exam = examWithQuestions(4, state: 'scheduled');

    expect(fn () => $this->start->handle($exam, $this->murid))
        ->toThrow(ExamWorkflowException::class, 'sedang tidak dibuka');
});

it('hands back the same attempt when a second tab starts the exam', function () {
    $exam = examWithQuestions(4);

    $first = $this->start->handle($exam, $this->murid);
    $second = $this->start->handle($exam, $this->murid);

    expect($second->id)->toBe($first->id)
        ->and(ExamAttempt::count())->toBe(1);
});

it('keeps the original start time when the student reconnects', function () {
    $exam = examWithQuestions(4);
    $first = $this->start->handle($exam, $this->murid);

    $this->travelTo(now()->addMinutes(15));
    $resumed = $this->start->handle($exam, $this->murid);

    // Reconnecting must not hand the student a fresh hour.
    expect($resumed->started_at->timestamp)->toBe($first->started_at->timestamp);
});

it('refuses to reopen an exam the student already handed in', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);
    $this->submit->handle($attempt);

    expect(fn () => $this->start->handle($exam, $this->murid))
        ->toThrow(ExamWorkflowException::class, 'sudah mengumpulkan');
});

it('saves an answer without deciding whether it is right', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);
    $question = $exam->questions()->first();

    $answer = $this->save->handle($attempt, $question->id, 'B');

    // Marking it now would put the answer key inside the student's own row
    // while the exam is still running.
    expect($answer->selected_option)->toBe('B')
        ->and($answer->is_correct)->toBeNull();
});

it('keeps one row when a student changes their mind', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);
    $question = $exam->questions()->first();

    $this->save->handle($attempt, $question->id, 'A');
    $this->save->handle($attempt, $question->id, 'C');

    expect(AttemptAnswer::where('exam_attempt_id', $attempt->id)->count())->toBe(1)
        ->and(AttemptAnswer::sole()->selected_option)->toBe('C');
});

it('keeps answers saved before the connection dropped', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);
    $questions = $exam->questions()->get();

    $this->save->handle($attempt, $questions[0]->id, 'A');
    $this->save->handle($attempt, $questions[1]->id, 'B');

    $this->travelTo(now()->addMinutes(10));
    $resumed = $this->start->handle($exam, $this->murid);

    expect($resumed->answers()->count())->toBe(2);
});

it('refuses an answer to a question from another exam', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);
    $stranger = Question::factory()->published()->create();

    expect(fn () => $this->save->handle($attempt, $stranger->id, 'A'))
        ->toThrow(ExamWorkflowException::class, 'bukan bagian dari ujian ini');
});

it('refuses an answer once the time is up', function () {
    $exam = examWithQuestions(4, ['duration_minutes' => 30]);
    $attempt = $this->start->handle($exam, $this->murid);
    $question = $exam->questions()->first();

    $this->travelTo(now()->addMinutes(31));

    expect(fn () => $this->save->handle($attempt, $question->id, 'A'))
        ->toThrow(ExamWorkflowException::class, 'Waktu ujian sudah habis');
});

it('scores the paper on the server when it is handed in', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);

    foreach (answerKeysOf($exam) as $questionId => $key) {
        $this->save->handle($attempt, $questionId, $key);
    }

    $result = $this->submit->handle($attempt);
    $attempt->refresh();

    expect($result->score)->toBe('100.00')
        ->and($attempt->score)->toBe('100.00')
        ->and($attempt->correct_count)->toBe(4)
        ->and($attempt->total_questions)->toBe(4)
        ->and($attempt->status)->toBe(AttemptStatus::Submitted)
        ->and($attempt->submitted_at)->not->toBeNull();
});

it('marks each answer right or wrong only at grading time', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);
    $keys = answerKeysOf($exam);
    $ids = array_keys($keys);

    $this->save->handle($attempt, $ids[0], $keys[$ids[0]]);
    $this->save->handle($attempt, $ids[1], $keys[$ids[1]] === 'A' ? 'B' : 'A');

    $this->submit->handle($attempt);

    expect(AttemptAnswer::where('question_id', $ids[0])->sole()->is_correct)->toBeTrue()
        ->and(AttemptAnswer::where('question_id', $ids[1])->sole()->is_correct)->toBeFalse();
});

it('counts questions the student never reached as wrong', function () {
    $exam = examWithQuestions(4);
    $attempt = $this->start->handle($exam, $this->murid);
    $keys = answerKeysOf($exam);
    $first = array_key_first($keys);

    $this->save->handle($attempt, $first, $keys[$first]);

    $result = $this->submit->handle($attempt);

    expect($result->score)->toBe('25.00')
        ->and($result->totalQuestions)->toBe(4);
});

it('accepts a submission in the last second', function () {
    $exam = examWithQuestions(2, ['duration_minutes' => 30]);
    $attempt = $this->start->handle($exam, $this->murid);

    $this->travelTo(now()->addMinutes(30)->subSecond());

    expect($this->submit->handle($attempt)->totalQuestions)->toBe(2);
});

it('rejects a submission thirty one seconds late', function () {
    $exam = examWithQuestions(2, ['duration_minutes' => 30]);
    $attempt = $this->start->handle($exam, $this->murid);

    $this->travelTo(now()->addMinutes(30)->addSeconds(31));

    expect(fn () => $this->submit->handle($attempt))
        ->toThrow(ExamWorkflowException::class, 'Waktu ujian sudah habis');

    expect($attempt->refresh()->submitted_at)->toBeNull();
});

it('refuses a second submission', function () {
    $exam = examWithQuestions(2);
    $attempt = $this->start->handle($exam, $this->murid);
    $this->submit->handle($attempt);

    expect(fn () => $this->submit->handle($attempt))
        ->toThrow(ExamWorkflowException::class, 'sudah mengumpulkan');
});

it('keeps one attempt per student even when two are opened at once', function () {
    $exam = examWithQuestions(2);
    $other = User::factory()->murid()->create();

    $this->start->handle($exam, $this->murid);
    $this->start->handle($exam, $other);
    $this->start->handle($exam, $this->murid);

    expect(ExamAttempt::where('exam_id', $exam->id)->count())->toBe(2);
});
