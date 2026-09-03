<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * The teachers who take this class.
     *
     * Many-to-many because a bimbel class is taught by several teachers for
     * different subjects, and a teacher takes several classes.
     *
     * @return BelongsToMany<User, $this>
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_teacher')->orderBy('users.name');
    }

    /** @return BelongsToMany<Exam, $this> */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_classroom');
    }

    public function displayName(): string
    {
        return "{$this->name} ({$this->academic_year})";
    }
}
