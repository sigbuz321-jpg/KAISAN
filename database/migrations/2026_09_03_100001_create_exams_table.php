<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('season_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->smallInteger('duration_minutes');
            $table->smallInteger('question_count');
            $table->decimal('difficulty_weight', 3, 2)->default(1.00);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->string('status', 9)->default('draft');
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['season_id', 'subject_id']);
        });

        DB::statement("ALTER TABLE exams ADD CONSTRAINT exams_status_check CHECK (status IN ('draft', 'scheduled', 'active', 'closed', 'graded'))");
        DB::statement('ALTER TABLE exams ADD CONSTRAINT exams_window_check CHECK (ends_at > starts_at)');
        DB::statement('ALTER TABLE exams ADD CONSTRAINT exams_duration_check CHECK (duration_minutes > 0)');

        // The leaderboard multiplies a score by this weight. Outside 1.00-2.00
        // a single exam could swamp a whole season.
        DB::statement('ALTER TABLE exams ADD CONSTRAINT exams_weight_check CHECK (difficulty_weight BETWEEN 1.00 AND 2.00)');
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
