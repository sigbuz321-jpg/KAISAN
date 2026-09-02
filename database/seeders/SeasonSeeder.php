<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        // Exams carry a season_id, so a fresh install needs one before a
        // teacher can schedule anything. A partial unique index allows only
        // one active season, which is why this is updateOrCreate and not
        // another row each time.
        Season::updateOrCreate(
            ['name' => 'Semester Ganjil 2026/2027'],
            ['starts_at' => now()->startOfYear(), 'ends_at' => null, 'is_active' => true],
        );
    }
}
