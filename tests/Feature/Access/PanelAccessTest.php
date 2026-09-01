<?php

use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->panel = Filament::getPanel('admin');
});

it('admits an admin to the panel', function () {
    expect(User::factory()->admin()->create()->canAccessPanel($this->panel))->toBeTrue();
});

it('admits a teacher to the panel', function () {
    expect(User::factory()->guru()->create()->canAccessPanel($this->panel))->toBeTrue();
});

it('keeps students out of the panel', function () {
    expect(User::factory()->murid()->create()->canAccessPanel($this->panel))->toBeFalse();
});

it('locks out a deactivated admin without deleting the account', function () {
    $admin = User::factory()->admin()->inactive()->create();

    expect($admin->canAccessPanel($this->panel))->toBeFalse()
        ->and($admin->exists)->toBeTrue();
});

it('redirects an anonymous visitor away from the panel', function () {
    $this->get('/admin')->assertRedirect();
});
