<?php

namespace App\Models;

use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $subject_id
 * @property int|null $topic_id
 * @property string $stem
 * @property array<string, string> $options
 * @property string $answer_key
 * @property string|null $explanation
 * @property int $difficulty
 * @property QuestionSource $source
 * @property QuestionStatus $status
 * @property int $created_by
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string $stem_hash
 * @property int $times_answered
 * @property int $times_correct
 * @property array<string, mixed>|null $ai_meta
 * @property-read Subject $subject
 * @property-read Topic|null $topic
 * @property-read User $author
 */
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    public const OPTION_KEYS = ['A', 'B', 'C', 'D'];

    protected $fillable = [
        'subject_id',
        'topic_id',
        'stem',
        'options',
        'answer_key',
        'explanation',
        'difficulty',
        'source',
        'status',
        'created_by',
    ];

    /**
     * Mirrors the column defaults, so a model that was just created reports
     * them instead of null. Without this, code reading a counter straight
     * after create() gets null rather than zero.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'difficulty' => 1200,
        'times_answered' => 0,
        'times_correct' => 0,
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'ai_meta' => 'array',
            'source' => QuestionSource::class,
            'status' => QuestionStatus::class,
            'approved_at' => 'datetime',
            'difficulty' => 'integer',
            'times_answered' => 'integer',
            'times_correct' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Question $question) {
            $question->stem_hash = self::hashStem($question->stem);
        });
    }

    /**
     * Duplicate detection keys on the wording alone, so the same question typed
     * with different spacing or capitalisation is still caught.
     */
    public static function hashStem(string $stem): string
    {
        return hash('sha256', Str::of($stem)->lower()->squish()->toString());
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

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Options in a fixed A-D order, whatever order they were stored in. */
    /** @return array<string, string> */
    public function orderedOptions(): array
    {
        $options = $this->options;

        return collect(self::OPTION_KEYS)
            ->filter(fn (string $key) => isset($options[$key]))
            ->mapWithKeys(fn (string $key) => [$key => $options[$key]])
            ->all();
    }

    public function isPublished(): bool
    {
        return $this->status === QuestionStatus::Published;
    }

    /** @param Builder<Question> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', QuestionStatus::Published);
    }

    /** @param Builder<Question> $query */
    public function scopeStatus(Builder $query, QuestionStatus $status): void
    {
        $query->where('status', $status);
    }
}
