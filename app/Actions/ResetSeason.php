<?php

namespace App\Actions;

use App\Models\Season;
use App\Services\Leaderboard\LeaderboardCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetSeason
{
    public function __construct(private readonly LeaderboardCalculator $calculator) {}

    /**
     * Closes the current season and opens a new one.
     *
     * What this does NOT do is the important part. Rule 5 of
     * .claude/rules/domain-kaisan.md: exam attempts, answers, marks and every
     * student's adaptive rating survive untouched. A reset clears the
     * scoreboard, not the school's records -- a parent asking about a mark from
     * last semester must still get an answer.
     *
     * The ending season keeps its final standings rather than having them
     * deleted, so last season's champions stay on the record.
     */
    public function handle(string $newSeasonName): Season
    {
        return DB::transaction(function () use ($newSeasonName) {
            $ending = Season::current();

            if ($ending !== null) {
                // One last recalculation before freezing: results submitted
                // since the last scheduled run must count towards the standings
                // that are about to become permanent.
                $this->calculator->recalculate($ending);

                $ending->update(['ended_at' => now(), 'is_active' => false]);

                Log::info('season ended', ['season_id' => $ending->id]);
            }

            // Only after the old one is deactivated: a partial unique index
            // allows exactly one active season, and the database would reject
            // this in the other order.
            $season = Season::create([
                'name' => $newSeasonName,
                'starts_at' => now(),
                'is_active' => true,
            ]);

            Log::info('season started', ['season_id' => $season->id]);

            return $season;
        });
    }
}
