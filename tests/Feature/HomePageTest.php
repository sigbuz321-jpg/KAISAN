<?php

use App\Models\User;

// Proves the whole chain is wired: Caddy -> PHP-FPM -> Laravel -> views -> lang/id.
it('serves the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('app.home.heading'));
});

it('renders the page in Indonesian', function () {
    expect(app()->getLocale())->toBe('id');

    $this->get('/')->assertSee('lang="id"', escape: false);
});

it('offers a way in for each of the three roles', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Murid')
        ->assertSee('Guru')
        ->assertSee('Admin')
        ->assertSee(route('masuk'), escape: false)
        ->assertSee('/guru/login', escape: false)
        ->assertSee('/admin/login', escape: false);
});

it('shows a signed-in student their way on instead of the three doors', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Lanjutkan belajar')
        ->assertDontSee('/admin/login', escape: false);
});

it('does not repeat a sign-in button in the header', function () {
    // The three doors below are the way in; a header button would compete.
    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'Masuk sebagai'))->toBe(1)
        ->and(preg_match('/<header.*?<\/header>/s', $html, $m))->toBe(1)
        ->and($m[0])->not->toContain('>Masuk<');
});
