<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Season> */
class SeasonFactory extends Factory
{
    protected static int $counter = 0;

    public function definition(): array
    {
        $n = ++static::$counter;

        return [
            'name' => "Semester {$n}",
            'starts_at' => now()->subMonth(),
            'ends_at' => null,
            // Not active by default: only one season may be, and a partial
            // unique index enforces it. Ask for active() when you need it.
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function ended(): static
    {
        return $this->state(fn () => ['ends_at' => now()->subDay(), 'is_active' => false]);
    }
}
