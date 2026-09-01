<?php

use App\Enums\Role;
use App\Models\User;

it('shows the installer when no admin exists yet', function () {
    $this->get('/setup')->assertOk()->assertSee('Buat akun admin pertama');
});

it('creates the first admin and signs them straight in', function () {
    $this->post('/setup', [
        'name' => 'Sigit Irawan',
        'email' => 'admin@kaisanbimbel.test',
        'password' => 'rahasia12345',
        'password_confirmation' => 'rahasia12345',
    ])->assertRedirect('/admin');

    $admin = User::where('email', 'admin@kaisanbimbel.test')->first();

    expect($admin->role)->toBe(Role::Admin)
        ->and($admin->is_active)->toBeTrue()
        ->and(auth()->id())->toBe($admin->id);
});

it('disappears once an admin exists', function () {
    User::factory()->admin()->create();

    $this->get('/setup')->assertNotFound();
    $this->post('/setup', [
        'name' => 'Penyusup',
        'email' => 'penyusup@example.test',
        'password' => 'rahasia12345',
        'password_confirmation' => 'rahasia12345',
    ])->assertNotFound();

    expect(User::query()->role(Role::Admin)->count())->toBe(1);
});

it('stays closed when SETUP_ENABLED is off', function () {
    config(['kaisan.setup_enabled' => false]);

    $this->get('/setup')->assertNotFound();
});

it('is not fooled by an existing non-admin account', function () {
    User::factory()->guru()->create();

    $this->get('/setup')->assertOk();
});

it('rejects a weak password', function () {
    $this->post('/setup', [
        'name' => 'Sigit',
        'email' => 'admin@kaisanbimbel.test',
        'password' => 'pendek',
        'password_confirmation' => 'pendek',
    ])->assertSessionHasErrors('password');

    expect(User::count())->toBe(0);
});
