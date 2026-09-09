<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Murid,
            'classroom_id' => Classroom::factory(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin, 'classroom_id' => null]);
    }

    public function guru(): static
    {
        return $this->state(fn () => ['role' => Role::Guru, 'classroom_id' => null]);
    }

    public function murid(): static
    {
        return $this->state(fn () => ['role' => Role::Murid]);
    }

    public function sd(int $grade = 1): static
    {
        return $this->state(fn () => [
            'role' => Role::Murid,
            'school_level' => \App\Enums\SchoolLevel::SD,
            'grade' => $grade,
        ]);
    }

    public function smp(int $grade = 7): static
    {
        return $this->state(fn () => [
            'role' => Role::Murid,
            'school_level' => \App\Enums\SchoolLevel::SMP,
            'grade' => $grade,
        ]);
    }

    public function sma(int $grade = 10): static
    {
        return $this->state(fn () => [
            'role' => Role::Murid,
            'school_level' => \App\Enums\SchoolLevel::SMA,
            'grade' => $grade,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
