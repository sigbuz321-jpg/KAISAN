<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * rating_before and rating_after are kept on every answer so a teacher can
 * explain to a parent exactly how a student's level moved, long after the fact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->char('selected_option', 1);
            $table->boolean('is_correct');
            $table->integer('rating_before');
            $table->integer('rating_after');
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->index('practice_session_id');
            $table->index('question_id');

            // One answer per question per session: practice never re-asks
            // within a sitting, so a repeat is a retried request.
            $table->unique(['practice_session_id', 'question_id']);
        });

        DB::statement("ALTER TABLE practice_answers ADD CONSTRAINT practice_answers_option_check CHECK (selected_option IN ('A', 'B', 'C', 'D'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_answers');
    }
};
