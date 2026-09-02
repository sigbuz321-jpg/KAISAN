<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Season;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Exam> */
class ExamFactory extends Factory
{
    protected static int $counter = 0;

    public function definition(): array
    {
        $n = ++static::$counter;

        return [
            'title' => "Ulangan Harian {$n}",
            'subject_id' => Subject::factory(),
            'season_id' => Season::factory(),
            'created_by' => User::factory()->guru(),
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(3),
            'duration_minutes' => 60,
            'question_count' => 5,
            'difficulty_weight' => '1.00',
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'status' => ExamStatus::Draft,
        ];
    }

    /** Already the default, but spelled out so every status reads the same way. */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => ExamStatus::Draft]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => ExamStatus::Scheduled]);
    }

    /** Open right now: started an hour ago, closes in an hour. */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ExamStatus::Active,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => ExamStatus::Closed,
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->subHour(),
        ]);
    }

    public function graded(): static
    {
        return $this->closed()->state(fn () => ['status' => ExamStatus::Graded]);
    }
}
