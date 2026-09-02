<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('difficulty', 6);
            $table->smallInteger('count');
            $table->string('status', 8)->default('queued');
            $table->string('model', 80)->nullable();
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->decimal('estimated_cost', 12, 4)->default(0);
            $table->text('error')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['requested_by', 'created_at']);
            $table->index('status');
        });

        DB::statement("ALTER TABLE ai_generation_jobs ADD CONSTRAINT ai_generation_jobs_status_check CHECK (status IN ('queued', 'running', 'done', 'failed'))");
        DB::statement("ALTER TABLE ai_generation_jobs ADD CONSTRAINT ai_generation_jobs_difficulty_check CHECK (difficulty IN ('easy', 'medium', 'hard'))");

        // The per-job ceiling from .claude/skills/ai-question-generation. It is a
        // cost guard as much as a validation rule, so the database holds it too.
        DB::statement('ALTER TABLE ai_generation_jobs ADD CONSTRAINT ai_generation_jobs_count_check CHECK (count BETWEEN 1 AND 20)');

        // Money and token counters are only ever added to.
        DB::statement('ALTER TABLE ai_generation_jobs ADD CONSTRAINT ai_generation_jobs_cost_check CHECK (estimated_cost >= 0 AND prompt_tokens >= 0 AND completion_tokens >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_jobs');
    }
};
