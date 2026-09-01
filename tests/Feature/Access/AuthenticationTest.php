<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('signs a student in and sends them to the front page', function () {
    $murid = User::factory()->murid()->create(['password' => 'rahasia12345']);

    $this->post('/masuk', ['email' => $murid->email, 'password' => 'rahasia12345'])
        ->assertRedirect('/');

    expect(auth()->id())->toBe($murid->id);
});

it('sends staff to the panel after signing in', function () {
    $guru = User::factory()->guru()->create(['password' => 'rahasia12345']);

    $this->post('/masuk', ['email' => $guru->email, 'password' => 'rahasia12345'])
        ->assertRedirect('/admin');
});

it('records when the account last signed in', function () {
    $murid = User::factory()->murid()->create(['password' => 'rahasia12345']);
    expect($murid->last_login_at)->toBeNull();

    $this->post('/masuk', ['email' => $murid->email, 'password' => 'rahasia12345']);

    expect($murid->refresh()->last_login_at)->not->toBeNull();
});

it('refuses a deactivated account and leaves nobody signed in', function () {
    $murid = User::factory()->murid()->inactive()->create(['password' => 'rahasia12345']);

    $this->post('/masuk', ['email' => $murid->email, 'password' => 'rahasia12345'])
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('gives the same message for a wrong password and an unknown email', function () {
    $murid = User::factory()->murid()->create(['password' => 'rahasia12345']);

    $wrongPassword = $this->post('/masuk', ['email' => $murid->email, 'password' => 'salah-sekali']);
    $unknownEmail = $this->post('/masuk', ['email' => 'tidak-ada@sekolah.test', 'password' => 'salah-sekali']);

    expect($wrongPassword->exception?->errors()['email'])
        ->toBe($unknownEmail->exception?->errors()['email']);
});

it('throttles repeated sign-in attempts', function () {
    foreach (range(1, 5) as $ignored) {
        $this->post('/masuk', ['email' => 'siapa@sekolah.test', 'password' => 'salah']);
    }

    $this->post('/masuk', ['email' => 'siapa@sekolah.test', 'password' => 'salah'])
        ->assertStatus(429);
});

it('signs the user out', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->post('/keluar')
        ->assertRedirect('/');

    expect(auth()->check())->toBeFalse();
});

it('changes a password when the old one is right', function () {
    $murid = User::factory()->murid()->create(['password' => 'rahasia12345']);

    $this->actingAs($murid)->put('/ganti-kata-sandi', [
        'current_password' => 'rahasia12345',
        'password' => 'sandibaru123',
        'password_confirmation' => 'sandibaru123',
    ])->assertSessionHas('status');

    expect(Hash::check('sandibaru123', $murid->refresh()->password))->toBeTrue();
});

it('refuses a password change when the old one is wrong', function () {
    $murid = User::factory()->murid()->create(['password' => 'rahasia12345']);

    $this->actingAs($murid)->put('/ganti-kata-sandi', [
        'current_password' => 'bukan-ini',
        'password' => 'sandibaru123',
        'password_confirmation' => 'sandibaru123',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('rahasia12345', $murid->refresh()->password))->toBeTrue();
});

it('emails a reset link and lets the user set a new password', function () {
    Notification::fake();
    $murid = User::factory()->murid()->create();

    $this->post('/lupa-kata-sandi', ['email' => $murid->email])->assertSessionHas('status');

    Notification::assertSentTo($murid, ResetPassword::class, function (ResetPassword $notification) use ($murid) {
        $this->post('/atur-ulang-kata-sandi', [
            'token' => $notification->token,
            'email' => $murid->email,
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertRedirect(route('masuk'));

        return true;
    });

    expect(Hash::check('sandibaru123', $murid->refresh()->password))->toBeTrue();
});

it('answers the same way for an unknown email so accounts cannot be probed', function () {
    Notification::fake();

    $this->post('/lupa-kata-sandi', ['email' => 'tidak-ada@sekolah.test'])
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});
