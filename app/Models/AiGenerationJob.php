<?php

namespace App\Models;

use App\Enums\AiJobStatus;
use App\Enums\DifficultyBand;
use Database\Factories\AiGenerationJobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One teacher's request for AI-written questions, and the record of what it cost.
 *
 * @property int $id
 * @property int $requested_by
 * @property int $subject_id
 * @property int|null $topic_id
 * @property DifficultyBand $difficulty
 * @property int $count
 * @property AiJobStatus $status
 * @property string|null $model
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property string $estimated_cost
 * @property string|null $error
 * @property Carbon|null $finished_at
 * @property array<string, mixed>|null $meta
 * @property-read User $requester
 * @property-read Subject $subject
 * @property-read Topic|null $topic
 */
class AiGenerationJob extends Model
{
    /** @use HasFactory<AiGenerationJobFactory> */
    use HasFactory;

    /** Cost guard from .claude/skills/ai-question-generation, mirrored by a CHECK constraint. */
    public const MAX_QUESTIONS_PER_JOB = 20;

    protected $fillable = [
        'requested_by',
        'subject_id',
        'topic_id',
        'difficulty',
        'count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => DifficultyBand::class,
            'status' => AiJobStatus::class,
            'count' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'finished_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Topic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function totalTokens(): int
    {
        return $this->prompt_tokens + $this->completion_tokens;
    }

    /** How many questions actually survived validation, which is often fewer than `count`. */
    public function savedCount(): int
    {
        return (int) ($this->meta['saved'] ?? 0);
    }

    public function rejectedCount(): int
    {
        return (int) ($this->meta['rejected'] ?? 0);
    }

    public function duplicateCount(): int
    {
        return (int) ($this->meta['duplicates'] ?? 0);
    }

    /** @param Builder<AiGenerationJob> $query */
    public function scopeRequestedBy(Builder $query, User $user): void
    {
        $query->where('requested_by', $user->id);
    }

    /** @param Builder<AiGenerationJob> $query */
    public function scopeStatus(Builder $query, AiJobStatus $status): void
    {
        $query->where('status', $status);
    }
}
