<?php

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('only lets students belong to a classroom', function () {
    $classroom = Classroom::factory()->create();

    expect(fn () => User::factory()->guru()->create(['classroom_id' => $classroom->id]))
        ->toThrow(QueryException::class);
});

it('attaches a student to a classroom', function () {
    $classroom = Classroom::factory()->create();
    $student = User::factory()->murid()->create(['classroom_id' => $classroom->id]);

    expect($student->classroom->is($classroom))->toBeTrue()
        ->and($classroom->students()->count())->toBe(1);
});

it('rejects an unknown role at the model layer', function () {
    expect(fn () => User::factory()->create(['role' => 'kepala_sekolah']))
        ->toThrow(ValueError::class);
});

it('rejects an unknown role at the database layer too', function () {
    // Defence in depth: the enum cast guards the application path, the CHECK
    // constraint guards anything that reaches the table another way.
    expect(fn () => DB::table('users')->insert([
        'name' => 'Penyusup',
        'email' => 'penyusup@example.test',
        'password' => 'x',
        'role' => 'kepala_sekolah',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('casts the role column to the enum', function () {
    expect(User::factory()->admin()->create()->role)->toBe(Role::Admin);
});
