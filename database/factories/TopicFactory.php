<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Topic> */
class TopicFactory extends Factory
{
    protected static int $counter = 0;

    public function definition(): array
    {
        $n = static::$counter++;

        return [
            'subject_id' => Subject::factory(),
            'name' => 'Bab '.($n + 1),
            'order' => $n,
        ];
    }
}
