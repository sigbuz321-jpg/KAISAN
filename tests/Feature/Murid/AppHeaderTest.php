<?php

use App\Models\User;

/**
 * The shared header in layouts/app.blade.php. Its two branches -- signed in and
 * signed out -- each used to carry controls that had nothing to do with the page
 * they sat on.
 */
it('offers the mute control to a student', function () {
    $this->actingAs(User::factory()->murid()->smp(7)->create())
        ->get(route('latihan.index'))
        ->assertOk()
        ->assertSee('data-react-island="sound-toggle"', false);
});

it('keeps the mute control away from a teacher', function () {
    // Only the student screens play anything, so a teacher was being offered a
    // control over sound that never reaches them.
    $this->actingAs(User::factory()->guru()->create())
        ->get(route('ganti-kata-sandi'))
        ->assertOk()
        ->assertDontSee('data-react-island="sound-toggle"', false);
});

it('keeps the mute control away from a signed-out visitor', function () {
    $this->get(route('masuk'))
        ->assertOk()
        ->assertDontSee('data-react-island="sound-toggle"', false);
});

it('does not link to the sign-in page from the sign-in page', function () {
    $response = $this->get(route('masuk'))->assertOk();

    // The form itself posts to masuk.store; what must not appear is a second
    // navigation link in the header pointing at the page already open.
    expect(substr_count($response->getContent(), 'href="'.route('masuk').'"'))->toBe(0);
});

it('leaves the forgotten password page one way back, not two', function () {
    $response = $this->get(route('lupa-kata-sandi'))->assertOk();

    // The page body offers "Kembali ke halaman masuk" and that one stays. What
    // the header must not do is add a second link to the same place.
    $response->assertSee('Kembali ke halaman masuk');

    expect(substr_count($response->getContent(), 'href="'.route('masuk').'"'))->toBe(1);
});
