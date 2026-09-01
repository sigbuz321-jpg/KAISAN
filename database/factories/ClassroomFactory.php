<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Classroom> */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        $grade = fake()->numberBetween(7, 12);

        return [
            'name' => 'Kelas '.$grade.fake()->randomElement(['A', 'B', 'C']),
            'grade' => $grade,
            'academic_year' => '2025/2026',
        ];
    }
}
