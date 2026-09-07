<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The computed standings, written by a scheduled job.
 *
 * Never calculated when a page is opened: with 500 students across dozens of
 * exams that would be the slowest query in the application, and it would run
 * again for every student who refreshed.
 *
 * A row with a null subject_id is the combined ranking across all subjects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('points', 10, 2);
            $table->integer('rank');
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index(['season_id', 'subject_id', 'points']);
        });

        // NULLS NOT DISTINCT so the combined rows, which carry a null
        // subject_id, are still one per student per season. Without it
        // PostgreSQL treats every null as unique and the guard would not apply
        // to exactly the rows most likely to be duplicated.
        DB::statement('CREATE UNIQUE INDEX leaderboard_entries_unique ON leaderboard_entries (season_id, subject_id, user_id) NULLS NOT DISTINCT');

        DB::statement('ALTER TABLE leaderboard_entries ADD CONSTRAINT leaderboard_entries_points_check CHECK (points >= 0)');
        DB::statement('ALTER TABLE leaderboard_entries ADD CONSTRAINT leaderboard_entries_rank_check CHECK (rank >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};
