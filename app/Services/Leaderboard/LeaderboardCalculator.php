<?php

namespace App\Services\Leaderboard;

use App\Enums\AttemptStatus;
use App\Models\Season;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes one season's standings.
 *
 * Two statements, whatever the number of students: PostgreSQL's RANK() does the
 * ordering in the database rather than PHP looping over thousands of rows.
 * docs/02-ARCHITECTURE.md picked PostgreSQL partly for this.
 *
 * RANK() rather than ROW_NUMBER() is deliberate. Tied students share a place
 * and the next place skips, which is what people expect and what avoids a
 * parent asking why two identical scores were ranked differently.
 */
class LeaderboardCalculator
{
    /**
     * Rewrites every entry for the season.
     *
     * Delete-then-insert inside one transaction rather than an upsert: readers
     * keep seeing the previous standings until the whole thing commits, so the
     * board is never briefly empty while the job runs.
     *
     * @return int rows written
     */
    public function recalculate(Season $season): int
    {
        return DB::transaction(function () use ($season) {
            DB::table('leaderboard_entries')->where('season_id', $season->id)->delete();

            $perSubject = $this->insertPerSubject($season);
            $combined = $this->insertCombined($season);

            return $perSubject + $combined;
        });
    }

    /** Standings within each subject. */
    private function insertPerSubject(Season $season): int
    {
        return DB::affectingStatement(<<<'SQL'
            INSERT INTO leaderboard_entries
                (season_id, subject_id, user_id, points, rank, computed_at, created_at, updated_at)
            SELECT
                e.season_id,
                e.subject_id,
                a.user_id,
                SUM(a.score * e.difficulty_weight) AS points,
                RANK() OVER (
                    PARTITION BY e.subject_id
                    ORDER BY SUM(a.score * e.difficulty_weight) DESC
                ) AS rank,
                NOW(), NOW(), NOW()
            FROM exam_attempts a
            JOIN exams e ON e.id = a.exam_id
            WHERE e.season_id = ?
              AND a.submitted_at IS NOT NULL
              AND a.voided_at IS NULL
              AND a.status = ?
              AND a.score IS NOT NULL
            GROUP BY e.season_id, e.subject_id, a.user_id
        SQL, [$season->id, AttemptStatus::Submitted->value]);
    }

    /** The overall board, stored with a null subject_id. */
    private function insertCombined(Season $season): int
    {
        return DB::affectingStatement(<<<'SQL'
            INSERT INTO leaderboard_entries
                (season_id, subject_id, user_id, points, rank, computed_at, created_at, updated_at)
            SELECT
                e.season_id,
                NULL,
                a.user_id,
                SUM(a.score * e.difficulty_weight) AS points,
                RANK() OVER (ORDER BY SUM(a.score * e.difficulty_weight) DESC) AS rank,
                NOW(), NOW(), NOW()
            FROM exam_attempts a
            JOIN exams e ON e.id = a.exam_id
            WHERE e.season_id = ?
              AND a.submitted_at IS NOT NULL
              AND a.voided_at IS NULL
              AND a.status = ?
              AND a.score IS NOT NULL
            GROUP BY e.season_id, a.user_id
        SQL, [$season->id, AttemptStatus::Submitted->value]);
    }
}
