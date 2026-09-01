<?php

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('imports students and puts them in the named classroom', function () {
    Classroom::factory()->create(['name' => 'Kelas 9A']);

    $import = runImport(<<<'CSV'
    name,email,classroom
    Budi Santoso,budi@sekolah.test,Kelas 9A
    Siti Aminah,siti@sekolah.test,Kelas 9A
    CSV);

    expect($import->successful_rows)->toBe(2)
        ->and(User::query()->role(Role::Murid)->count())->toBe(2);

    $budi = User::where('email', 'budi@sekolah.test')->first();
    expect($budi->role)->toBe(Role::Murid)
        ->and($budi->is_active)->toBeTrue()
        ->and($budi->classroom->name)->toBe('Kelas 9A');
});

it('gives every imported student the password the admin chose', function () {
    Classroom::factory()->create(['name' => 'Kelas 9A']);

    runImport(<<<'CSV'
    name,email,classroom
    Budi Santoso,budi@sekolah.test,Kelas 9A
    CSV);

    expect(Hash::check('rahasia123', User::where('email', 'budi@sekolah.test')->first()->password))->toBeTrue();
});

it('fails a row whose classroom does not exist instead of inventing one', function () {
    $import = runImport(<<<'CSV'
    name,email,classroom
    Budi Santoso,budi@sekolah.test,Kelas Hantu
    CSV);

    expect($import->getFailedRowsCount())->toBe(1)
        ->and(User::where('email', 'budi@sekolah.test')->exists())->toBeFalse()
        ->and(Classroom::count())->toBe(0);
});

it('updates rather than duplicates when the same file is imported twice', function () {
    Classroom::factory()->create(['name' => 'Kelas 9A']);

    $csv = <<<'CSV'
    name,email,classroom
    Budi Santoso,budi@sekolah.test,Kelas 9A
    CSV;

    runImport($csv);
    runImport($csv);

    expect(User::where('email', 'budi@sekolah.test')->count())->toBe(1);
});
