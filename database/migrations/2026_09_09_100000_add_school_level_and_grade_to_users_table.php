<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('school_level', 10)->nullable()->after('role');
            $table->unsignedTinyInteger('grade')->nullable()->after('school_level');

            $table->index('school_level');
            $table->index('grade');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_school_level_check CHECK (school_level IS NULL OR school_level IN ('sd', 'smp', 'sma'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_grade_check CHECK (grade IS NULL OR (grade >= 1 AND grade <= 12))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_school_level_only_for_students CHECK (school_level IS NULL OR role = 'murid')");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_grade_only_for_students CHECK (grade IS NULL OR role = 'murid')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_grade_only_for_students');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_school_level_only_for_students');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_grade_check');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_school_level_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['school_level']);
            $table->dropIndex(['grade']);
            $table->dropColumn(['school_level', 'grade']);
        });
    }
};
