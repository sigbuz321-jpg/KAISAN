<?php

use App\Enums\SchoolLevel;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('allows a student to have a school level and grade', function () {
    $student = User::factory()->murid()->create([
        'school_level' => SchoolLevel::SMP,
        'grade' => 8,
    ]);

    expect($student->school_level)->toBe(SchoolLevel::SMP)
        ->and($student->grade)->toBe(8)
        ->and($student->effectiveSchoolLevel())->toBe(SchoolLevel::SMP)
        ->and($student->effectiveGrade())->toBe(8);
});

it('derives school level and grade from classroom if not explicitly set', function () {
    $classroom = Classroom::factory()->create(['grade' => 9]);
    $student = User::factory()->murid()->create([
        'classroom_id' => $classroom->id,
        'school_level' => null,
        'grade' => null,
    ]);

    expect($student->effectiveGrade())->toBe(9)
        ->and($student->effectiveSchoolLevel())->toBe(SchoolLevel::SMP);
});

it('prefers explicit grade and school level over classroom', function () {
    $classroom = Classroom::factory()->create(['grade' => 9]);
    $student = User::factory()->murid()->create([
        'classroom_id' => $classroom->id,
        'school_level' => SchoolLevel::SMA,
        'grade' => 10,
    ]);

    expect($student->effectiveGrade())->toBe(10)
        ->and($student->effectiveSchoolLevel())->toBe(SchoolLevel::SMA);
});

it('rejects school_level on staff accounts at database layer', function () {
    expect(fn () => DB::table('users')->insert([
        'name' => 'Guru Jenjang',
        'email' => 'gurujenjang@example.test',
        'password' => 'x',
        'role' => 'guru',
        'school_level' => 'smp',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects grade on staff accounts at database layer', function () {
    expect(fn () => DB::table('users')->insert([
        'name' => 'Admin Kelas',
        'email' => 'adminkelas@example.test',
        'password' => 'x',
        'role' => 'admin',
        'grade' => 7,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects invalid school_level value at database layer', function () {
    expect(fn () => DB::table('users')->insert([
        'name' => 'Murid Invalid',
        'email' => 'muridinvalid@example.test',
        'password' => 'x',
        'role' => 'murid',
        'school_level' => 'universitas',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects grade outside 1-12 at database layer', function () {
    expect(fn () => DB::table('users')->insert([
        'name' => 'Murid TK',
        'email' => 'muridtk@example.test',
        'password' => 'x',
        'role' => 'murid',
        'grade' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('users')->insert([
        'name' => 'Murid Kuliah',
        'email' => 'muridkuliah@example.test',
        'password' => 'x',
        'role' => 'murid',
        'grade' => 13,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
