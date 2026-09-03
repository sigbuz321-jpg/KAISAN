<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which classes sit an exam.
 *
 * Until now every active student could see every exam, because nothing tied an
 * exam to a class. With 500 students across several classes that is wrong, and
 * it also made "belum mengerjakan" impossible to report: the system could not
 * know who was supposed to be there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_classroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['exam_id', 'classroom_id']);
            $table->index('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_classroom');
    }
};
