<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /** Enough students to rehearse an exam with, without slowing every reset down. */
    private const STUDENTS_PER_CLASSROOM = 20;

    public const PASSWORD = 'rahasia123';

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kaisan.test'],
            ['name' => 'Admin Bimbel', 'password' => self::PASSWORD, 'role' => Role::Admin, 'is_active' => true],
        );

        foreach ([['guru@kaisan.test', 'Ibu Sari'], ['guru2@kaisan.test', 'Pak Budi']] as [$email, $name]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => self::PASSWORD, 'role' => Role::Guru, 'is_active' => true],
            );
        }

        $this->students();
        $this->teachingAssignments();
    }

    /**
     * Give each teacher some classes, so the results pages are reachable on a
     * fresh install without anyone having to wire it up by hand first.
     */
    private function teachingAssignments(): void
    {
        $classrooms = Classroom::query()->orderBy('name')->get();

        $sari = User::where('email', 'guru@kaisan.test')->first();
        $budi = User::where('email', 'guru2@kaisan.test')->first();

        // Deliberately overlapping but not identical: it makes the difference
        // between the two teachers visible when testing the results page.
        $sari?->taughtClassrooms()->sync($classrooms->take(2)->pluck('id'));
        $budi?->taughtClassrooms()->sync($classrooms->skip(1)->pluck('id'));
    }

    private function students(): void
    {
        $names = $this->nameParts();
        $number = 1;

        foreach ([['7A', 7], ['7B', 7], ['8A', 8]] as [$label, $grade]) {
            $classroom = Classroom::updateOrCreate(
                ['name' => "Kelas {$label}", 'academic_year' => '2026/2027'],
                ['grade' => $grade],
            );

            for ($i = 0; $i < self::STUDENTS_PER_CLASSROOM; $i++) {
                User::updateOrCreate(
                    ['email' => "murid{$number}@kaisan.test"],
                    [
                        'name' => $names[($number - 1) % count($names)].' '.$number,
                        'password' => self::PASSWORD,
                        'role' => Role::Murid,
                        'classroom_id' => $classroom->id,
                        'is_active' => true,
                    ],
                );

                $number++;
            }
        }
    }

    /**
     * Plain Indonesian given names rather than Faker output: the client is
     * shown this data during demos, and "Dr. Zachariah Bogisich" does not look
     * like a bimbel roster.
     *
     * @return list<string>
     */
    private function nameParts(): array
    {
        return [
            'Adinda', 'Bagas', 'Citra', 'Dimas', 'Eka', 'Fajar', 'Gita', 'Hafiz',
            'Intan', 'Joko', 'Kirana', 'Lukman', 'Maya', 'Nanda', 'Oki', 'Putri',
            'Rizky', 'Sinta', 'Tirta', 'Umi',
        ];
    }
}
