<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::subjects() as $name => $topics) {
            $subject = Subject::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );

            foreach (array_values($topics) as $order => $topic) {
                Topic::updateOrCreate(
                    ['subject_id' => $subject->id, 'name' => $topic],
                    ['order' => $order],
                );
            }
        }
    }

    /** @return array<string, list<string>> */
    public static function subjects(): array
    {
        return [
            'Matematika' => ['Bilangan Bulat', 'Pecahan', 'Aljabar', 'Perbandingan'],
            'IPA' => ['Besaran dan Satuan', 'Zat dan Wujudnya', 'Sistem Pencernaan'],
            'Bahasa Indonesia' => ['Teks Deskripsi', 'Teks Prosedur', 'Puisi Rakyat'],
        ];
    }
}
