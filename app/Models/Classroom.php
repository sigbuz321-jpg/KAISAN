<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $grade
 * @property string $academic_year
 * @property-read Collection<int, User> $students
 */
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    protected $fillable = ['name', 'grade', 'academic_year'];

    protected function casts(): array
    {
        return ['grade' => 'integer'];
    }

    /** @return HasMany<User, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function displayName(): string
    {
        return "{$this->name} ({$this->academic_year})";
    }
}
