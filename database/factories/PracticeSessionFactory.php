<?php

namespace Database\Factories;

use App\Models\PracticeSession;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PracticeSession> */
class PracticeSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->murid(),
            'subject_id' => Subject::factory(),
            'started_at' => now(),
            'ended_at' => null,
            'questions_count' => 0,
            'correct_count' => 0,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn () => ['ended_at' => now()]);
    }
}
