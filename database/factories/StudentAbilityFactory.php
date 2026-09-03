<?php

namespace Database\Factories;

use App\Models\StudentAbility;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudentAbility> */
class StudentAbilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->murid(),
            'subject_id' => Subject::factory(),
            'rating' => StudentAbility::startingRating(),
            'answers_count' => 0,
        ];
    }

    public function rated(int $rating, int $answers = 0): static
    {
        return $this->state(fn () => ['rating' => $rating, 'answers_count' => $answers]);
    }
}
