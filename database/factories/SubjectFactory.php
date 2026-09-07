<?php

namespace Database\Factories;

use App\Enums\SchoolLevel;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subject> */
class SubjectFactory extends Factory
{
    protected static int $counter = 0;

    public function definition(): array
    {
        $names = ['Matematika', 'IPA', 'IPS', 'Bahasa Indonesia', 'Bahasa Inggris', 'Pendidikan Pancasila'];
        $n = static::$counter++;
        $name = $names[$n % count($names)].($n >= count($names) ? ' '.(intdiv($n, count($names)) + 1) : '');

        return [
            'name' => $name,
            'school_level' => null,
            'start_grade' => null,
            'end_grade' => null,
            'slug' => str($name)->slug()->toString(),
            'is_active' => true,
        ];
    }

    public function sd(): static
    {
        return $this->state(fn () => [
            'school_level' => SchoolLevel::SD,
            'start_grade' => 1,
            'end_grade' => 6,
        ]);
    }

    public function smp(): static
    {
        return $this->state(fn () => [
            'school_level' => SchoolLevel::SMP,
            'start_grade' => 7,
            'end_grade' => 9,
        ]);
    }
}
