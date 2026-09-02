<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seasons belong to M6 on the roadmap, but exams carry a season_id from the
 * day they exist -- an exam scored outside a season could never be ranked.
 * Only the table lands here. The leaderboard, the aggregation job and the
 * reset screen remain M6's work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Exactly one season may be active. A partial unique index makes the
        // database refuse a second one, rather than trusting every code path
        // that will ever touch this table.
        DB::statement('CREATE UNIQUE INDEX one_active_season ON seasons (is_active) WHERE is_active');

        DB::statement('ALTER TABLE seasons ADD CONSTRAINT seasons_period_check CHECK (ends_at IS NULL OR ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
