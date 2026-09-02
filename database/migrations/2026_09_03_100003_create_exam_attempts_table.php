<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->smallInteger('correct_count')->nullable();
            $table->smallInteger('total_questions');
            $table->string('status', 11)->default('in_progress');
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamps();

            // Rule 2 of .claude/rules/domain-kaisan.md: one attempt per student
            // per exam, unless a teacher explicitly reopens it. The database
            // enforces it so a double submit from two tabs cannot create two.
            $table->unique(['exam_id', 'user_id']);
            $table->index(['user_id', 'submitted_at']);
        });

        DB::statement("ALTER TABLE exam_attempts ADD CONSTRAINT exam_attempts_status_check CHECK (status IN ('in_progress', 'submitted', 'voided'))");
        DB::statement('ALTER TABLE exam_attempts ADD CONSTRAINT exam_attempts_score_check CHECK (score IS NULL OR (score >= 0 AND score <= 100))');

        // Rule 6: a result is never deleted. Voiding it demands a reason, so
        // the bimbel can always explain the record to a parent.
        DB::statement('ALTER TABLE exam_attempts ADD CONSTRAINT exam_attempts_void_check CHECK (voided_at IS NULL OR voided_reason IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
