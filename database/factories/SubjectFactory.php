<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subject> */
class SubjectFactory extends Factory
{
    protected static int $counter = 0;

    public function definition(): array
    {
        $names = ['Matematika', 'IPA', 'IPS', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKn'];
        $n = static::$counter++;
        $name = $names[$n % count($names)].($n >= count($names) ? ' '.(intdiv($n, count($names)) + 1) : '');

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_active' => true,
        ];
    }
}
