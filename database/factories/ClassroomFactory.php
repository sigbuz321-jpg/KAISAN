<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Classroom> */
class ClassroomFactory extends Factory
{
    /**
     * Names are generated from a counter rather than randomly: classrooms carry
     * a unique (name, academic_year) index, and a random pick out of a handful
     * of grade/letter combinations collides often enough to make tests flaky.
     */
    protected static int $counter = 0;

    public function definition(): array
    {
        $n = static::$counter++;
        $grade = 7 + ($n % 6);
        $letter = chr(65 + intdiv($n, 6) % 26);

        return [
            'name' => "Kelas {$grade}{$letter}",
            'grade' => $grade,
            'academic_year' => '2025/2026',
        ];
    }
}
