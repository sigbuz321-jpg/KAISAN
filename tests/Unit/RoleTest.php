<?php

use App\Enums\Role;

it('lets staff into the panel but never students', function () {
    expect(Role::Admin->canAccessPanel())->toBeTrue()
        ->and(Role::Guru->canAccessPanel())->toBeTrue()
        ->and(Role::Murid->canAccessPanel())->toBeFalse();
});

it('grants panel access to exactly two roles and no others', function () {
    // A role added later must be an explicit decision. If this fails, decide
    // whether the new role belongs in the panel -- do not just widen the list.
    $allowed = array_values(array_filter(
        Role::cases(),
        fn (Role $role) => $role->canAccessPanel(),
    ));

    expect($allowed)->toBe([Role::Admin, Role::Guru]);
});

it('labels every role in Indonesian', function () {
    expect(Role::options())->toBe([
        'admin' => 'Admin',
        'guru' => 'Guru',
        'murid' => 'Murid',
    ]);
});
