<?php

namespace App\Filament\Guru\Widgets;

use App\Enums\AiJobStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionStatus;
use App\Models\AiGenerationJob;
use App\Models\Exam;
use App\Models\Question;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
    /** How long the review queue count may lag. Short enough to feel live. */
    private const COUNT_TTL = 30;

    protected string $view = 'filament.guru.widgets.guru-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * Cached because the navigation badge asks for the same number as the
     * widget, on every panel request. A static property would do it too, but
     * would go stale under a persistent worker.
     */
    public static function antrianCount(): int
    {
        return Cache::remember(
            'questions:review-count',
            self::COUNT_TTL,
            fn () => Question::query()->where('status', QuestionStatus::Review)->count(),
        );
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

    /**
     * Whether this viewer may see money at all.
     *
     * The monthly recap is the owner's business, not a teacher's, per
     * AiGenerationJobPolicy::viewCostReport(). A teacher still sees what their
     * own requests cost, because that is the figure they can actually act on.
     */
    public function bolehLihatTotalBiaya(): bool
    {
        return auth()->user()?->can('viewCostReport', AiGenerationJob::class) ?? false;
    }

    public function biayaAiBulanIni(): float
    {
        $user = auth()->user();

        return (float) AiGenerationJob::query()
            ->where('status', AiJobStatus::Done)
            ->where('created_at', '>=', now()->startOfMonth())
            ->unless($this->bolehLihatTotalBiaya(),
                fn ($query) => $query->where('requested_by', $user?->id))
            ->sum('estimated_cost');
    }

    /** The budget is a whole-bimbel figure, so only whoever pays it sees it. */
    public function batasBiayaAi(): ?float
    {
        if (! $this->bolehLihatTotalBiaya()) {
            return null;
        }

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
