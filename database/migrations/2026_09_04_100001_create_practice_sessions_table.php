<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->smallInteger('questions_count')->default(0);
            $table->smallInteger('correct_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'subject_id']);
        });

        DB::statement('ALTER TABLE practice_sessions ADD CONSTRAINT practice_sessions_counts_check CHECK (correct_count >= 0 AND correct_count <= questions_count)');
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_sessions');
    }
};
