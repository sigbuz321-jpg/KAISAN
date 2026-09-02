<?php

namespace App\Filament\Pages;

use App\Enums\AiJobStatus;
use App\Models\AiGenerationJob;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What the AI has cost, month by month.
 *
 * The client pays the router directly and the amount varies with use, so
 * .claude/rules/domain-kaisan.md requires them to be able to see the breakdown
 * themselves rather than take an invoice on trust.
 */
class AiCostReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Biaya AI';

    protected static ?string $title = 'Rekap biaya AI';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.ai-cost-report';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewCostReport', AiGenerationJob::class) ?? false;
    }

    /**
     * One grouped query rather than a month-by-month loop. `saved` lives in the
     * meta JSONB column, which is exactly what docs/02-ARCHITECTURE.md picked
     * PostgreSQL for.
     *
     * @return list<array{month: string, jobs: int, questions: int, tokens: int, cost: string}>
     */
    public function rows(): array
    {
        // The query builder rather than Eloquent: these are aggregates, not
        // AiGenerationJob records, and hydrating a model for them would be a
        // lie about what comes back.
        return DB::table('ai_generation_jobs')
            ->where('status', AiJobStatus::Done->value)
            ->selectRaw("date_trunc('month', created_at) as month")
            ->selectRaw('count(*) as jobs')
            ->selectRaw("coalesce(sum((meta->>'saved')::int), 0) as questions")
            ->selectRaw('coalesce(sum(prompt_tokens + completion_tokens), 0) as tokens')
            ->selectRaw('coalesce(sum(estimated_cost), 0) as cost')
            ->groupBy('month')
            ->orderByDesc('month')
            ->get()
            ->map(fn (object $row) => [
                'month' => Carbon::parse((string) $row->month)->translatedFormat('F Y'),
                'jobs' => (int) $row->jobs,
                'questions' => (int) $row->questions,
                'tokens' => (int) $row->tokens,
                'cost' => (string) $row->cost,
            ])
            ->all();
    }

    /** Spend so far this month, for comparison against AI_MONTHLY_BUDGET. */
    public function currentMonthCost(): float
    {
        return (float) AiGenerationJob::query()
            ->where('status', AiJobStatus::Done)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('estimated_cost');
    }

    public function monthlyBudget(): ?float
    {
        $budget = config('services.ai_router.monthly_budget');

        return is_numeric($budget) ? (float) $budget : null;
    }

    public function overBudget(): bool
    {
        $budget = $this->monthlyBudget();

        return $budget !== null && $budget > 0 && $this->currentMonthCost() > $budget;
    }
}
