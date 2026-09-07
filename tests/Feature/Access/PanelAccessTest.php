<?php

use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->admin = Filament::getPanel('admin');
    $this->guru = Filament::getPanel('guru');
});

it('admits an admin to the admin panel', function () {
    expect(User::factory()->admin()->create()->canAccessPanel($this->admin))->toBeTrue();
});

it('admits an admin to the teacher panel as well', function () {
    expect(User::factory()->admin()->create()->canAccessPanel($this->guru))->toBeTrue();
});

it('admits a teacher to the teacher panel', function () {
    expect(User::factory()->guru()->create()->canAccessPanel($this->guru))->toBeTrue();
});

it('keeps a teacher out of the admin panel', function () {
    expect(User::factory()->guru()->create()->canAccessPanel($this->admin))->toBeFalse();
});

it('keeps students out of the teacher panel', function () {
    expect(User::factory()->murid()->create()->canAccessPanel($this->guru))->toBeFalse();
});

it('keeps students out of the admin panel', function () {
    expect(User::factory()->murid()->create()->canAccessPanel($this->admin))->toBeFalse();
});

it('locks out a deactivated admin without deleting the account', function () {
    $admin = User::factory()->admin()->inactive()->create();

    expect($admin->canAccessPanel($this->admin))->toBeFalse()
        ->and($admin->canAccessPanel($this->guru))->toBeFalse()
        ->and($admin->exists)->toBeTrue();
});

it('locks out a deactivated teacher', function () {
    expect(User::factory()->guru()->inactive()->create()->canAccessPanel($this->guru))->toBeFalse();
});

it('redirects an anonymous visitor away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});

it('redirects an anonymous visitor away from the teacher panel', function () {
    $this->get('/guru')->assertRedirect();
});
