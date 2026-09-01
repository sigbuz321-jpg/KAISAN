<?php

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;

it('imports 500 students from one file', function () {
    foreach (['Kelas 9A', 'Kelas 9B', 'Kelas 9C', 'Kelas 9D', 'Kelas 9E'] as $name) {
        Classroom::factory()->create(['name' => $name]);
    }

    $rows = ['name,email,classroom'];
    for ($i = 1; $i <= 500; $i++) {
        $kelas = 'Kelas 9'.chr(65 + ($i % 5));
        $rows[] = "Murid Ke {$i},murid{$i}@sekolah.test,{$kelas}";
    }

    $started = microtime(true);
    $import = runImport(implode("\n", $rows));
    $seconds = microtime(true) - $started;

    expect($import->successful_rows)->toBe(500)
        ->and($import->getFailedRowsCount())->toBe(0)
        ->and(User::query()->role(Role::Murid)->count())->toBe(500);

    fwrite(STDERR, sprintf("\n  [skala] 500 murid terimpor dalam %.2f detik\n", $seconds));
})->group('scale');
