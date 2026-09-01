<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->smallInteger('grade');
            $table->string('academic_year', 9); // e.g. 2025/2026
            $table->timestamps();

            $table->unique(['name', 'academic_year']);
            $table->index(['academic_year', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
