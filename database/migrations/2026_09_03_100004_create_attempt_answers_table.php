<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->char('selected_option', 1)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at');
            $table->integer('time_spent_ms')->nullable();
            $table->timestamps();

            $table->index('exam_attempt_id');

            // One row per question. Changing an answer updates the row rather
            // than adding another, so a partial save can be repeated safely
            // when a flaky connection retries it.
            $table->unique(['exam_attempt_id', 'question_id']);
        });

        DB::statement("ALTER TABLE attempt_answers ADD CONSTRAINT attempt_answers_option_check CHECK (selected_option IS NULL OR selected_option IN ('A', 'B', 'C', 'D'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
