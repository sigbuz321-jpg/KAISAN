<?php

use App\Enums\Role;

it('lets staff into the panel but never students', function () {
    expect(Role::Admin->canAccessPanel())->toBeTrue()
        ->and(Role::Guru->canAccessPanel())->toBeTrue()
        ->and(Role::Murid->canAccessPanel())->toBeFalse();
});

it('labels every role in Indonesian', function () {
    expect(Role::options())->toBe([
        'admin' => 'Admin',
        'guru' => 'Guru',
        'murid' => 'Murid',
    ]);
});
