<?php

namespace App\Models;

use App\Enums\QuestionStatus;
use App\Enums\SchoolLevel;
use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property SchoolLevel|null $school_level
 * @property int|null $start_grade
 * @property int|null $end_grade
 * @property string $slug
 * @property bool $is_active
 */
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'school_level',
        'start_grade',
        'end_grade',
        'slug',
        'is_active',
    ];

    /**
     * Mirrors the column defaults so a freshly created model reports them
     * rather than null. See coding-style.md.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'school_level' => SchoolLevel::class,
            'start_grade' => 'integer',
            'end_grade' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Subject $subject) {
            if (blank($subject->slug)) {
                $suffix = $subject->school_level ? '-'.$subject->school_level->value : '';
                $subject->slug = Str::slug($subject->name.$suffix);
            }
        });
    }

    /**
     * Human-readable label that includes school level when present (without parentheses).
     * Used in reference tables where both SD and SMP appear together.
     */
    public function displayName(): string
    {
        if ($this->school_level === null) {
            return $this->name;
        }

        return "{$this->name} {$this->school_level->label()}";
    }

    /**
     * Plain subject name without school level suffix.
     * Used in forms where school level is already selected separately.
     */
    public function nameOnly(): string
    {
        return $this->name;
    }

    /**
     * Returns grade range text, e.g. "Kelas 1–6" or "Kelas 7–9".
     */
    public function gradeRangeLabel(): ?string
    {
        if ($this->start_grade === null && $this->end_grade === null) {
            return null;
        }

        if ($this->start_grade !== null && $this->end_grade !== null) {
            return $this->start_grade === $this->end_grade
                ? "Kelas {$this->start_grade}"
                : "Kelas {$this->start_grade}–{$this->end_grade}";
        }

        return $this->start_grade !== null
            ? "Mulai Kelas {$this->start_grade}"
            : "Sampai Kelas {$this->end_grade}";
    }

    /** @param Builder<Subject> $query */
    public function scopeForSchoolLevel(Builder $query, SchoolLevel|string $level): void
    {
        $value = $level instanceof SchoolLevel ? $level->value : $level;
        $query->where('school_level', $value);
    }

    /** @param Builder<Subject> $query */
    public function scopeForGrade(Builder $query, int $grade): void
    {
        $query->where(function (Builder $q) use ($grade) {
            $q->whereNull('start_grade')->orWhere('start_grade', '<=', $grade);
        })->where(function (Builder $q) use ($grade) {
            $q->whereNull('end_grade')->orWhere('end_grade', '>=', $grade);
        });
    }

    /**
     * The subjects one student is allowed to be offered: their own school
     * level, their grade, and subjects that predate levels entirely.
     *
     * Both the practice list and the leaderboard read through this scope. They
     * used to filter separately, and the leaderboard was missed -- an SMP
     * student was offered boards for subjects that only exist in SD.
     *
     * @param  Builder<Subject>  $query
     */
    public function scopeVisibleTo(Builder $query, User $student): void
    {
        $level = $student->effectiveSchoolLevel();
        $grade = $student->effectiveGrade();

        $query->where('is_active', true)
            ->when($level, fn (Builder $q) => $q->where(function (Builder $inner) use ($level) {
                $inner->whereNull('school_level')->orWhere('school_level', $level->value);
            }))
            ->when($grade, fn (Builder $q) => $q->forGrade($grade));
    }

    /**
     * Subjects whose question bank has something a student can be given.
     *
     * Kept separate from visibleTo() on purpose: one scope answers "may this
     * student see it", the other "is there anything in it". Reading them
     * together at the call site is how you can tell which rule dropped a row.
     *
     * @param  Builder<Subject>  $query
     */
    public function scopeWithPublishedQuestions(Builder $query): void
    {
        $query->whereHas('questions', fn (Builder $q) => $q->where('status', QuestionStatus::Published));
    }

    /** @return HasMany<Topic, $this> */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('order');
    }

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
