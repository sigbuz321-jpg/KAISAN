<?php

use App\Enums\Role;
use App\Enums\SchoolLevel;
use App\Models\Classroom;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    $this->guru = User::factory()->guru()->create(['name' => 'Ibu Guru']);
    $this->murid = User::factory()->murid()->create([
        'name' => 'Budi Santoso',
        'school_level' => SchoolLevel::SMP,
        'grade' => 7,
    ]);
});

it('lets a teacher open the student account list in guru panel', function () {
    $this->actingAs($this->guru)
        ->get('/guru/users')
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertDontSee('Admin Utama');
});

it('lets an admin see all accounts in admin panel', function () {
    $this->actingAs($this->admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('Ibu Guru')
        ->assertSee('Admin Utama');
});

it('lets teacher view, create, and update student accounts', function () {
    expect($this->guru->can('viewAny', User::class))->toBeTrue()
        ->and($this->guru->can('view', $this->murid))->toBeTrue()
        ->and($this->guru->can('create', User::class))->toBeTrue()
        ->and($this->guru->can('update', $this->murid))->toBeTrue();
});

it('stops teacher from viewing or updating admin and other teacher accounts', function () {
    $otherGuru = User::factory()->guru()->create();

    expect($this->guru->can('view', $this->admin))->toBeFalse()
        ->and($this->guru->can('update', $this->admin))->toBeFalse()
        ->and($this->guru->can('view', $otherGuru))->toBeFalse()
        ->and($this->guru->can('update', $otherGuru))->toBeFalse();
});

it('stops a student from managing any accounts or viewing account list', function () {
    expect($this->murid->can('viewAny', User::class))->toBeFalse()
        ->and($this->murid->can('create', User::class))->toBeFalse()
        ->and($this->murid->can('update', $this->murid))->toBeFalse();

    $this->actingAs($this->murid)
        ->get('/admin/users')
        ->assertForbidden();

    $this->actingAs($this->murid)
        ->get('/guru/users')
        ->assertForbidden();
});
