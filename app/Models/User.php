<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Role $role
 * @property int|null $classroom_id
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property-read Classroom|null $classroom
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'classroom_id',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isGuru(): bool
    {
        return $this->role === Role::Guru;
    }

    public function isMurid(): bool
    {
        return $this->role === Role::Murid;
    }

    /**
     * Panel access is staff-only, and a deactivated account keeps its history
     * but loses its way in.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->role->canAccessPanel();
    }

    /** @param Builder<User> $query */
    public function scopeRole(Builder $query, Role $role): void
    {
        $query->where('role', $role);
    }

    /** @param Builder<User> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
