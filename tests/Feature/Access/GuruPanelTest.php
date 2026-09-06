<?php

use App\Models\Classroom;
use App\Models\User;

/**
 * The teacher panel discovers the same Resource classes as the admin panel, so
 * every route the admin panel has also exists under /guru. These tests are what
 * stops that convenience from quietly becoming a way around the Policies.
 */
beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
});

it('lets a teacher into their own dashboard', function () {
    $this->actingAs($this->guru)->get('/guru')->assertOk();
});

it('refuses the account list to a teacher in their own panel', function () {
    $this->actingAs($this->guru)->get('/guru/users')->assertForbidden();
});

it('never shows a student name to a teacher through the teacher dashboard', function () {
    User::factory()->murid()->create(['name' => 'Budi Santoso']);

    $this->actingAs($this->guru)
        ->get('/guru')
        ->assertOk()
        ->assertDontSee('Budi Santoso');
});

it('has no AI cost report route in the teacher panel', function () {
    $this->actingAs($this->guru)->get('/guru/ai-cost-report')->assertNotFound();
});

it('shows a teacher only the classrooms they teach', function () {
    $mine = Classroom::factory()->create(['name' => 'Kelas 7A']);
    Classroom::factory()->create(['name' => 'Kelas 9B']);

    $this->guru->taughtClassrooms()->attach($mine);

    $this->actingAs($this->guru)
        ->get('/guru/classrooms')
        ->assertOk()
        ->assertSee('Kelas 7A')
        ->assertDontSee('Kelas 9B');
});

it('shows an admin every classroom', function () {
    Classroom::factory()->create(['name' => 'Kelas 7A']);
    Classroom::factory()->create(['name' => 'Kelas 9B']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/classrooms')
        ->assertOk()
        ->assertSee('Kelas 7A')
        ->assertSee('Kelas 9B');
});
