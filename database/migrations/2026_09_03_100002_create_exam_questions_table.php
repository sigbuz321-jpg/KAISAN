<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The questions an exam is made of, frozen at scheduling time.
 *
 * restrictOnDelete on question_id is deliberate: a question that has appeared
 * in an exam can be archived but never deleted, or old results would stop
 * making sense.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->smallInteger('order');
            $table->timestamps();

            $table->index(['exam_id', 'order']);
            $table->unique(['exam_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
