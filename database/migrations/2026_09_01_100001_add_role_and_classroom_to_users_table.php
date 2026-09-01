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
            $table->string('role', 10)->default('murid')->after('password');
            $table->foreignId('classroom_id')->nullable()->after('role')
                ->constrained('classrooms')->nullOnDelete();
            // Deactivating is not deleting: rule 7 in .claude/rules/domain-kaisan.md
            // keeps former students attached to their past exam attempts.
            $table->boolean('is_active')->default(true)->after('classroom_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index('role');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'guru', 'murid'))");

        // Only students belong to a classroom.
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_classroom_only_for_students CHECK (classroom_id IS NULL OR role = 'murid')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_classroom_only_for_students');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropConstrainedForeignId('classroom_id');
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
    }
};
