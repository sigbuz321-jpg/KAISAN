<?php

namespace App\Jobs;

use App\Models\Season;
use App\Services\Leaderboard\LeaderboardCalculator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Recomputes the active season's standings.
 *
 * Runs on a schedule rather than when a ranking page is opened. With 500
 * students and dozens of exams, computing on request would be the slowest
 * query in the application, and it would run again for every refresh.
 *
 * Unique, so a slow run is never overlapped by the next tick.
 */
class RecalculateLeaderboard implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function uniqueId(): string
    {
        return 'leaderboard';
    }

    public function handle(LeaderboardCalculator $calculator): void
    {
        $season = Season::current();

        if ($season === null) {
            // Nothing to rank against. Not an error: a fresh installation has
            // no season until the admin creates one.
            Log::info('leaderboard skipped, no active season');

            return;
        }

        $written = $calculator->recalculate($season);

        Log::info('leaderboard recalculated', [
            'season_id' => $season->id,
            'entries' => $written,
        ]);
    }
}
