<?php

namespace App\Livewire\Murid;

use App\Actions\SaveAttemptAnswer;
use App\Actions\StartExamAttempt;
use App\Actions\SubmitExamAttempt;
use App\Exceptions\ExamWorkflowException;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamPaper;
use App\Services\Exams\ExamWindow;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The screen a student sits an exam on.
 *
 * The paper is fetched once in mount() and kept in component state. Moving
 * between questions is then free -- .claude/rules/performance.md forbids
 * re-querying on every navigation, and with 150 students at once the
 * difference is the site staying up.
 *
 * Nothing here decides anything that matters. The deadline, the marking and
 * whether a submission is accepted all come from the server; this screen only
 * shows them.
 */
class PengerjaanUjian extends Component
{
    /** Locked: a tampered payload must not be able to point at someone else's attempt. */
    #[Locked]
    public int $attemptId = 0;

    #[Locked]
    public int $examId = 0;

    /** @var list<array{id: int, number: int, stem: string, options: array<string, string>}> */
    #[Locked]
    public array $paper = [];

    /** @var array<int, string|null> question id => the letter shown on screen */
    public array $answers = [];

    public int $current = 0;

    /**
     * Unix seconds. The browser counts down from these two rather than from
     * its own clock, which may be wrong, paused, or edited.
     */
    #[Locked]
    public int $deadlineAt = 0;

    #[Locked]
    public int $serverNow = 0;

    public bool $selesai = false;

    public ?string $skor = null;

    public ?string $pesan = null;

    public function mount(Exam $exam, StartExamAttempt $start, ExamPaper $paper, ExamWindow $window): void
    {
        Gate::authorize('start', $exam);

        try {
            $attempt = $start->handle($exam, auth()->user());
        } catch (ExamWorkflowException $e) {
            $this->redirectRoute('ujian.index', navigate: true);
            session()->flash('pesan', $e->getMessage());

            return;
        }

        $this->examId = $exam->id;
        $this->attemptId = $attempt->id;
        $this->paper = $paper->forStudent($attempt);
        $this->answers = $this->savedAnswers($attempt, $paper);
        $this->deadlineAt = $window->deadlineFor($attempt)->timestamp;
        $this->serverNow = now()->timestamp;
    }

    public function pilih(int $questionId, string $option): void
    {
        if ($this->selesai) {
            return;
        }

        $attempt = $this->attempt();
        Gate::authorize('update', $attempt);

        try {
            app(SaveAttemptAnswer::class)->handle($attempt, $questionId, $option);
            $this->answers[$questionId] = $option;
            $this->pesan = null;
        } catch (ExamWorkflowException $e) {
            $this->pesan = $e->getMessage();

            // Close the screen only when it really is over. A rejected answer
            // for some other reason should not lock a student out of an exam
            // they still have time to finish.
            $this->selesai = $attempt->isSubmitted()
                || app(ExamWindow::class)->hasExpired($attempt);
        }
    }

    /**
     * Fired by wire:model when a student picks an option.
     *
     * `answers` is bound to the browser and so is not trusted: this hands the
     * value to SaveAttemptAnswer, which checks the question belongs to this
     * exam, and grading reads from the database rather than from here.
     */
    public function updatedAnswers(mixed $value, string $key): void
    {
        if (is_string($value) && $value !== '') {
            $this->pilih((int) $key, $value);
        }
    }

    public function ke(int $index): void
    {
        $this->current = max(0, min($index, count($this->paper) - 1));
    }

    public function kumpulkan(): void
    {
        if ($this->selesai) {
            return;
        }

        $attempt = $this->attempt();
        Gate::authorize('submit', $attempt);

        try {
            $result = app(SubmitExamAttempt::class)->handle($attempt);
            $this->skor = $result->score;
            $this->selesai = true;
            $this->pesan = null;
        } catch (ExamWorkflowException $e) {
            $this->pesan = $e->getMessage();
            $this->selesai = true;
        }
    }

    /** How many questions still have no answer, for the confirmation prompt. */
    public function belumDijawab(): int
    {
        return count(array_filter(
            $this->paper,
            fn (array $q) => ($this->answers[$q['id']] ?? null) === null,
        ));
    }

    public function render(): View
    {
        return view('livewire.murid.pengerjaan-ujian')
            ->layout('layouts.app');
    }

    private function attempt(): ExamAttempt
    {
        return ExamAttempt::with('exam')->findOrFail($this->attemptId);
    }

    /**
     * Answers already stored, translated back to the letters this student sees.
     * Without the translation a returning student would find the wrong option
     * ticked on a shuffled paper.
     *
     * @return array<int, string|null>
     */
    private function savedAnswers(ExamAttempt $attempt, ExamPaper $paper): array
    {
        $questions = $attempt->exam->questions()->get()->keyBy('id');

        return $attempt->answers()
            ->get()
            ->mapWithKeys(fn ($answer) => [
                $answer->question_id => $questions->has($answer->question_id)
                    ? $paper->toDisplayedOption($attempt, $questions[$answer->question_id], $answer->selected_option)
                    : null,
            ])
            ->all();
    }
}
