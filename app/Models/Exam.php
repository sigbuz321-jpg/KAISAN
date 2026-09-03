<?php

namespace App\Models;

use App\Enums\ExamStatus;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property int $subject_id
 * @property int $season_id
 * @property int $created_by
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $duration_minutes
 * @property int $question_count
 * @property string $difficulty_weight
 * @property bool $shuffle_questions
 * @property bool $shuffle_options
 * @property ExamStatus $status
 * @property-read Subject $subject
 * @property-read Season $season
 */
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'subject_id',
        'season_id',
        'created_by',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'question_count',
        'difficulty_weight',
        'shuffle_questions',
        'shuffle_options',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'question_count' => 'integer',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'status' => ExamStatus::class,
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<Question, $this> */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('order')
            ->orderBy('exam_questions.order');
    }

    /** @return HasMany<ExamAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /** The classes sitting this exam. A student outside them never sees it. */
    /** @return BelongsToMany<Classroom, $this> */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'exam_classroom')->orderBy('classrooms.name');
    }

    public function targetsClassroom(?int $classroomId): bool
    {
        if ($classroomId === null) {
            return false;
        }

        return $this->classrooms()->whereKey($classroomId)->exists();
    }

    /** The exam's own closing time, before any per-student deadline is applied. */
    public function closesAt(): Carbon
    {
        return $this->ends_at;
    }

    /** @param Builder<Exam> $query */
    public function scopeVisibleToStudents(Builder $query): void
    {
        $query->where('status', '!=', ExamStatus::Draft);
    }

    /** @param Builder<Exam> $query */
    public function scopeStatus(Builder $query, ExamStatus $status): void
    {
        $query->where('status', $status);
    }
}
