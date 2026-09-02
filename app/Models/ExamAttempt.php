<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One student's run at one exam.
 *
 * @property int $id
 * @property int $exam_id
 * @property int $user_id
 * @property Carbon $started_at
 * @property Carbon|null $submitted_at
 * @property string|null $score
 * @property int|null $correct_count
 * @property int $total_questions
 * @property AttemptStatus $status
 * @property Carbon|null $voided_at
 * @property string|null $voided_reason
 * @property int|null $reopened_by
 * @property Carbon|null $reopened_at
 * @property-read Exam $exam
 * @property-read User $student
 */
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'user_id',
        'started_at',
        'total_questions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'voided_at' => 'datetime',
            'reopened_at' => 'datetime',
            'correct_count' => 'integer',
            'total_questions' => 'integer',
            'status' => AttemptStatus::class,
        ];
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<AttemptAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /** Only a submitted, unvoided attempt contributes points to a season. */
    public function countsTowardsRanking(): bool
    {
        return $this->isSubmitted() && ! $this->isVoided();
    }

    /** @param Builder<ExamAttempt> $query */
    public function scopeRanked(Builder $query): void
    {
        $query->whereNotNull('submitted_at')->whereNull('voided_at');
    }

    /** @param Builder<ExamAttempt> $query */
    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', AttemptStatus::InProgress);
    }
}
