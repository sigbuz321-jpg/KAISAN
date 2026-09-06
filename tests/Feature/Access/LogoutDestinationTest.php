<?php

use App\Models\User;

/**
 * Signing out should always land on the front page, where all three ways in
 * are listed. Filament's own response returns to the panel's login screen,
 * which leaves a teacher on the teacher login with no visible route across to
 * the student side.
 */
it('returns a student to the front page', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->post('/keluar')
        ->assertRedirect(route('beranda'));

    expect(auth()->check())->toBeFalse();
});

it('returns a teacher to the front page', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->post('/guru/logout')
        ->assertRedirect(route('beranda'));

    expect(auth()->check())->toBeFalse();
});

it('returns an admin to the front page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/admin/logout')
        ->assertRedirect(route('beranda'));

    expect(auth()->check())->toBeFalse();
});
