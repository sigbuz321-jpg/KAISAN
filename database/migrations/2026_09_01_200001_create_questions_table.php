<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->text('stem');
            $table->jsonb('options');
            $table->char('answer_key', 1);
            $table->text('explanation')->nullable();
            $table->integer('difficulty')->default(1200); // Elo scale
            $table->string('source', 10)->default('manual');
            $table->string('status', 12)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('stem_hash', 64);
            $table->integer('times_answered')->default(0);
            $table->integer('times_correct')->default(0);
            $table->jsonb('ai_meta')->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'difficulty', 'status']);
            $table->index('status');
            $table->unique(['subject_id', 'stem_hash']);
        });

        DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_source_check CHECK (source IN ('manual', 'ai'))");
        DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_status_check CHECK (status IN ('draft', 'review', 'published', 'archived'))");
        DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_answer_key_check CHECK (answer_key IN ('A', 'B', 'C', 'D'))");

        // Exactly four options, per docs/03-DATABASE.md.
        DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_four_options CHECK (jsonb_array_length(jsonb_path_query_array(options, '\$.*')) = 4)");

        // The key must name an option that actually exists. jsonb_exists() is
        // used instead of the ? operator, which PDO would read as a binding.
        DB::statement('ALTER TABLE questions ADD CONSTRAINT questions_answer_key_exists CHECK (jsonb_exists(options, answer_key))');
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
