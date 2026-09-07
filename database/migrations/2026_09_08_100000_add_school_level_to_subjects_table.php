<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('school_level', 10)->nullable()->after('name');
            $table->unsignedTinyInteger('start_grade')->nullable()->after('school_level');
            $table->unsignedTinyInteger('end_grade')->nullable()->after('start_grade');

            $table->index('school_level');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['school_level']);
            $table->dropColumn(['school_level', 'start_grade', 'end_grade']);
        });
    }
};
