<?php

namespace App\Livewire\Murid;

use App\Actions\AnswerPracticeQuestion;
use App\Actions\EndPracticeSession;
use App\Actions\StartPracticeSession;
use App\Enums\AbilityLevel;
use App\Exceptions\PracticeException;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\StudentAbility;
use App\Models\Subject;
use App\Services\Adaptive\QuestionPicker;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The practice screen.
 *
 * One question at a time, answered and marked immediately. That is the whole
 * difference from an exam: here the point is finding out you were wrong while
 * you still remember why you chose it.
 *
 * The question in state carries no answer key. It arrives only in the feedback,
 * after the answer has been recorded and can no longer be changed.
 */
class LatihanAdaptif extends Component
{
    #[Locked]
    public int $subjectId = 0;

    #[Locked]
    public int $sessionId = 0;

    /** @var array{id: int, stem: string, options: array<string, string>}|null */
    #[Locked]
    public ?array $soal = null;

    public ?string $pilihan = null;

    /** @var array{benar: bool, kunci: string, pembahasan: string|null, naikLevel: bool}|null */
    #[Locked]
    public ?array $umpanBalik = null;

    #[Locked]
    public string $level = '';

    #[Locked]
    public string $levelKeterangan = '';

    #[Locked]
    public int $progres = 0;

    #[Locked]
    public int $dijawab = 0;

    #[Locked]
    public int $benar = 0;

    #[Locked]
    public bool $habis = false;

    public bool $selesai = false;

    public function mount(Subject $subject, StartPracticeSession $start): void
    {
        $student = auth()->user();
        abort_unless($student?->isMurid() && $student->is_active, 403);

        if ($subject->school_level !== null && $student->effectiveSchoolLevel() !== null) {
            abort_if($subject->school_level !== $student->effectiveSchoolLevel(), 403, 'Mata pelajaran tidak sesuai dengan jenjang Anda.');
        }

        if ($student->effectiveGrade() !== null) {
            if ($subject->start_grade !== null && $student->effectiveGrade() < $subject->start_grade) {
                abort(403, 'Mata pelajaran tidak sesuai dengan kelas Anda.');
            }
            if ($subject->end_grade !== null && $student->effectiveGrade() > $subject->end_grade) {
                abort(403, 'Mata pelajaran tidak sesuai dengan kelas Anda.');
            }
        }

        $session = $start->handle($student, $subject);

        $this->subjectId = $subject->id;
        $this->sessionId = $session->id;
        $this->dijawab = $session->questions_count;
        $this->benar = $session->correct_count;

        $this->refreshLevel();
        $this->ambilSoal();
    }

    public function jawab(): void
    {
        if ($this->pilihan === null || $this->umpanBalik !== null || $this->soal === null) {
            return;
        }

        $question = Question::findOrFail($this->soal['id']);

        try {
            $outcome = app(AnswerPracticeQuestion::class)
                ->handle($this->session(), $question, $this->pilihan);
        } catch (PracticeException $e) {
            $this->addError('pilihan', $e->getMessage());

            return;
        }

        $this->umpanBalik = [
            'benar' => $outcome->correct,
            'kunci' => $outcome->answerKey,
            'pembahasan' => $outcome->explanation,
            // Only worth telling a student about; a few points of rating is not.
            'naikLevel' => $outcome->levelChanged(),
        ];

        $this->dijawab++;
        $this->benar += $outcome->correct ? 1 : 0;

        $this->refreshLevel();

        // Hand off to the React Micro-Island layered on top of this Livewire view:
        // Motion.dev handles the spring bounce, confetti and the level-up modal,
        // while the Web Audio API engine plays the matching tone. The
        // server has already recorded the answer and updated the rating, so this
        // is purely a celebratory signal — re-fetching it would risk a double
        // tap that the answers() guard in AnswerPracticeQuestion already blocks.
        $this->dispatch('latihan-feedback', [
            'benar' => $outcome->correct,
            'kunci' => $outcome->answerKey,
            'pembahasan' => $outcome->explanation,
            'naikLevel' => $outcome->levelChanged(),
            'level' => $this->level,
            'dijawab' => $this->dijawab,
            'benar_count' => $this->benar,
        ]);
    }

    public function berikutnya(): void
    {
        $this->pilihan = null;
        $this->umpanBalik = null;
        $this->resetErrorBag();

        $this->ambilSoal();
    }

    public function akhiri(EndPracticeSession $end): void
    {
        $end->handle($this->session());

        $this->selesai = true;
        $this->soal = null;

        // Signal the React Micro-Island to play the celebratory finished-chord
        // and a final lighter confetti burst as the summary renders. This is a
        // one-shot cue — the next page-load resets the overlay.
        $this->dispatch('latihan-selesai', [
            'dijawab' => $this->dijawab,
            'benar_count' => $this->benar,
        ]);
    }

    public function render(): View
    {
        return view('livewire.murid.latihan-adaptif')->layout('layouts.app', ['tab' => 'latihan']);
    }

    /**
     * Fetches the next question and strips it to what a student may see.
     *
     * Same rule as the exam screen: the answer key never enters component
     * state, because everything there is serialised into the page.
     */
    private function ambilSoal(): void
    {
        $ability = $this->ability();
        $sessionAnsweredIds = $this->session()->answers()->pluck('question_id')->all();

        $picked = app(QuestionPicker::class)->pick(
            auth()->user(),
            $this->subjectId,
            $ability->rating,
            $sessionAnsweredIds,
        );

        if (! $picked->found()) {
            $this->soal = null;
            $this->habis = true;

            return;
        }

        $this->soal = [
            'id' => $picked->question->id,
            'stem' => $picked->question->stem,
            'options' => $picked->question->orderedOptions(),
        ];
    }

    private function refreshLevel(): void
    {
        $ability = $this->ability();

        // The band, never the number: a visible score invites children to
        // compare themselves with each other.
        $level = AbilityLevel::forRating($ability->rating);

        $this->level = $level->label();
        $this->levelKeterangan = $level->description();
        $this->progres = $ability->progress();
    }

    private function ability(): StudentAbility
    {
        return StudentAbility::query()
            ->where('user_id', auth()->id())
            ->where('subject_id', $this->subjectId)
            ->firstOrFail();
    }

    private function session(): PracticeSession
    {
        return PracticeSession::findOrFail($this->sessionId);
    }
}
