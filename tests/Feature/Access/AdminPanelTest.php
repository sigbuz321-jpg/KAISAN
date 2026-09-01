<?php

use App\Models\Classroom;
use App\Models\User;

it('lets an admin open the account list', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/users')
        ->assertOk();
});

it('refuses the account list to a teacher', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get('/admin/users')
        ->assertForbidden();
});

it('lets a teacher see classrooms', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get('/admin/classrooms')
        ->assertOk();
});

it('keeps a student out of the panel entirely', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('keeps a deactivated admin out of the panel', function () {
    $this->actingAs(User::factory()->admin()->inactive()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('shows an admin the accounts that exist', function () {
    $classroom = Classroom::factory()->create(['name' => 'Kelas 9A']);
    User::factory()->murid()->create(['name' => 'Budi Santoso', 'classroom_id' => $classroom->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Budi Santoso');
});
