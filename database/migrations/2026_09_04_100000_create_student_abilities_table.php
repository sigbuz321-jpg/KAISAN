<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One rating per student per subject.
 *
 * Per subject, not global: a student can be strong in Matematika and weak in
 * IPA, and a single number would serve neither.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->integer('rating')->default(1200);
            $table->integer('answers_count')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'subject_id']);
        });

        // The bounds from .claude/skills/adaptive-difficulty. Enforced here as
        // well as in the service: a rating outside them would send question
        // selection somewhere it can never find anything.
        DB::statement('ALTER TABLE student_abilities ADD CONSTRAINT student_abilities_rating_check CHECK (rating BETWEEN 400 AND 2400)');
        DB::statement('ALTER TABLE student_abilities ADD CONSTRAINT student_abilities_count_check CHECK (answers_count >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('student_abilities');
    }
};
