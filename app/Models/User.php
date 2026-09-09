<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\SchoolLevel;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Role $role
 * @property SchoolLevel|null $school_level
 * @property int|null $grade
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
        'school_level',
        'grade',
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
            'school_level' => SchoolLevel::class,
            'grade' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (! $user->isMurid()) {
                $user->school_level = null;
                $user->grade = null;
                $user->classroom_id = null;
            } elseif ($user->grade !== null && $user->school_level === null) {
                $user->school_level = SchoolLevel::fromGrade($user->grade);
            }
        });
    }

    public function effectiveGrade(): ?int
    {
        return $this->grade ?? $this->classroom?->grade;
    }

    public function effectiveSchoolLevel(): ?SchoolLevel
    {
        return $this->school_level ?? SchoolLevel::fromGrade($this->effectiveGrade());
    }

    public function gradeLabel(): ?string
    {
        $grade = $this->effectiveGrade();

        return $grade !== null ? "Kelas {$grade}" : null;
    }

    public function schoolLevelLabel(): ?string
    {
        return $this->effectiveSchoolLevel()?->label();
    }

    /** The class a student belongs to. Always null for staff. */
    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * The classes a teacher takes.
     *
     * Separate from `classroom` above, which is a student's own class. This is
     * what .claude/rules/security.md means by "the classes a teacher teaches".
     *
     * @return BelongsToMany<Classroom, $this>
     */
    public function taughtClassrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_teacher')->orderBy('classrooms.name');
    }

    public function teaches(?int $classroomId): bool
    {
        if ($classroomId === null || ! $this->isGuru()) {
            return false;
        }

        return $this->taughtClassrooms()->whereKey($classroomId)->exists();
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
        if (! $this->is_active || ! $this->role->canAccessPanel()) {
            return false;
        }

        // Teachers land in their own panel; an admin may open either, because
        // the admin panel is where accounts, subjects and billing live.
        return $panel->getId() === 'guru' || $this->isAdmin();
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
