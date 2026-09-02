<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Development and demo data.
 *
 * Everything here uses well-known passwords, so it must never touch a real
 * installation. The guard below is deliberate: `migrate:fresh --seed` is a
 * habit, and habits travel to the wrong terminal.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'DatabaseSeeder berisi data contoh dengan kata sandi yang mudah ditebak '
                .'dan tidak boleh dijalankan di produksi. Buat akun admin lewat halaman /setup.'
            );
        }

        $this->call([
            AccountSeeder::class,
            CurriculumSeeder::class,
            QuestionSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('Akun contoh (kata sandi semuanya: rahasia123)');
        $this->command->table(['Peran', 'Email'], [
            ['Admin', 'admin@kaisan.test'],
            ['Guru', 'guru@kaisan.test'],
            ['Guru', 'guru2@kaisan.test'],
            ['Murid', 'murid1@kaisan.test'],
        ]);
    }
}
