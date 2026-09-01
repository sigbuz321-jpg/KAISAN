<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use RuntimeException;

class CreateFirstAdmin
{
    /** @param array{name: string, email: string, password: string} $data */
    public function handle(array $data): User
    {
        // Checked again here, not just in the controller: this is the only
        // guard that survives someone wiring the action up somewhere else.
        if (self::alreadyDone()) {
            throw new RuntimeException('Akun admin sudah ada.');
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => Role::Admin,
            'is_active' => true,
        ]);
    }

    public static function alreadyDone(): bool
    {
        return User::query()->role(Role::Admin)->exists();
    }

    public static function isAvailable(): bool
    {
        return config('kaisan.setup_enabled') === true && ! self::alreadyDone();
    }
}
