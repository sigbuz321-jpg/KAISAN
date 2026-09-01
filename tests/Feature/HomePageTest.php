<?php

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
