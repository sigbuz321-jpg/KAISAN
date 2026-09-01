<?php

namespace Database\Factories;

use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    protected static int $counter = 0;

    public function definition(): array
    {
        $n = static::$counter++;

        return [
            'subject_id' => Subject::factory(),
            'topic_id' => null,
            // Unique wording: questions carry a unique (subject_id, stem_hash).
            'stem' => "Berapakah hasil dari {$n} + {$n}?",
            'options' => ['A' => (string) ($n * 2), 'B' => (string) ($n + 1), 'C' => (string) $n, 'D' => '0'],
            'answer_key' => 'A',
            'explanation' => 'Jumlahkan kedua bilangan.',
            'difficulty' => 1200,
            'source' => QuestionSource::Manual,
            'status' => QuestionStatus::Draft,
            'created_by' => User::factory()->guru(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => QuestionStatus::Published,
            'approved_at' => now(),
        ]);
    }

    public function review(): static
    {
        return $this->state(fn () => ['status' => QuestionStatus::Review]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => QuestionStatus::Archived]);
    }

    public function fromAi(): static
    {
        return $this->state(fn () => ['source' => QuestionSource::Ai]);
    }
}
