<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which classes a teacher takes.
 *
 * .claude/rules/security.md restricts student data to the classes a teacher
 * teaches, but nothing recorded that, so authorship of the exam had to stand in
 * for it. This is the relation the rule actually asks for.
 *
 * Many-to-many on purpose: a bimbel teacher takes several classes, and a class
 * is taught by several teachers for different subjects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['classroom_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_teacher');
    }
};
