<?php

namespace App\Filament\Guru\Widgets;

use App\Enums\AiJobStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionStatus;
use App\Models\AiGenerationJob;
use App\Models\Exam;
use App\Models\Question;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The one thing a teacher should see on opening the panel: what is waiting for
 * them, what is coming, and what it is costing.
 *
 * Every figure is read from tables that already exist. The design package
 * assumed a questions.cost_cents column; this project records AI spend per job
 * in ai_generation_jobs instead, which is more accurate and already the
 * evidence trail the client is billed against, so no migration is needed.
 */
class GuruOverview extends Widget
{
    protected string $view = 'filament.guru.widgets.guru-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * Memoised because the navigation badge asks for the same number as the
     * widget, and a teacher should not pay for that query twice per request.
     */
    private static ?int $reviewCount = null;

    public static function antrianCount(): int
    {
        return self::$reviewCount ??= Question::query()
            ->where('status', QuestionStatus::Review)
            ->count();
    }

    public function antrianBaruMingguIni(): int
    {
        return Question::query()
            ->where('status', QuestionStatus::Review)
            ->where('created_at', '>=', now()->subWeek())
            ->count();
    }

    public function ujianAkanDatang(): int
    {
        return Exam::query()
            ->where('status', ExamStatus::Scheduled)
            ->where('starts_at', '>', now())
            ->count();
    }

    public function ujianBerikutnya(): ?Exam
    {
        return Exam::query()
            ->with('subject:id,name')
            ->where('status', ExamStatus::Scheduled)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();
    }

    public function biayaAiBulanIni(): float
    {
        return (float) AiGenerationJob::query()
            ->where('status', AiJobStatus::Done)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('estimated_cost');
    }

    public function batasBiayaAi(): ?float
    {
        $budget = config('services.ai_router.monthly_budget');

        return is_numeric($budget) ? (float) $budget : null;
    }

    /** @return Collection<int, Question> */
    public function antrianList(): Collection
    {
        return Question::query()
            ->select(['id', 'stem', 'subject_id', 'source', 'created_at'])
            ->with('subject:id,name')
            ->where('status', QuestionStatus::Review)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function bulanIni(): string
    {
        return Carbon::now()->translatedFormat('F Y');
    }
}
