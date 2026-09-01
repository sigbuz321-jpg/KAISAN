<?php

use App\Models\Classroom;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->guru = User::factory()->guru()->create();
    $this->murid = User::factory()->murid()->create();
});

it('lets only the admin manage accounts', function () {
    expect($this->admin->can('viewAny', User::class))->toBeTrue()
        ->and($this->guru->can('viewAny', User::class))->toBeFalse()
        ->and($this->murid->can('viewAny', User::class))->toBeFalse();
});

it('stops a teacher from reading another account', function () {
    expect($this->guru->can('view', $this->murid))->toBeFalse();
});

it('stops a student from reading another account', function () {
    $other = User::factory()->murid()->create();

    expect($this->murid->can('view', $other))->toBeFalse();
});

it('lets anyone read their own account', function () {
    expect($this->murid->can('view', $this->murid))->toBeTrue();
});

it('never allows deleting an account, not even for the admin', function () {
    expect($this->admin->can('delete', $this->murid))->toBeFalse();
});

it('lets the admin deactivate others but not themselves', function () {
    expect($this->admin->can('deactivate', $this->murid))->toBeTrue()
        ->and($this->admin->can('deactivate', $this->admin))->toBeFalse();
});

it('refuses to delete a classroom that still has students', function () {
    $classroom = Classroom::factory()->create();
    User::factory()->murid()->create(['classroom_id' => $classroom->id]);

    expect($this->admin->can('delete', $classroom))->toBeFalse();
});

it('allows deleting an empty classroom', function () {
    expect($this->admin->can('delete', Classroom::factory()->create()))->toBeTrue();
});

it('lets teachers see classrooms but not change them', function () {
    $classroom = Classroom::factory()->create();

    expect($this->guru->can('viewAny', Classroom::class))->toBeTrue()
        ->and($this->guru->can('update', $classroom))->toBeFalse()
        ->and($this->guru->can('create', Classroom::class))->toBeFalse();
});
