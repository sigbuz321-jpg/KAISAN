<?php

namespace App\Models;

use Database\Factories\PracticeSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One sitting of practice.
 *
 * Practice never earns leaderboard points -- rule 4 of domain-kaisan.md -- so
 * nothing here feeds the ranking. It exists so a student can see what they did
 * and a teacher can see they are working.
 *
 * @property int $id
 * @property int $user_id
 * @property int $subject_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property int $questions_count
 * @property int $correct_count
 */
class PracticeSession extends Model
{
    /** @use HasFactory<PracticeSessionFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'subject_id', 'started_at', 'ended_at', 'questions_count', 'correct_count'];

    /**
     * Mirrors the column defaults so a freshly created model reports them
     * rather than null. See coding-style.md: this bug has appeared three times.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'questions_count' => 0,
        'correct_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'questions_count' => 'integer',
            'correct_count' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return HasMany<PracticeAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(PracticeAnswer::class);
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }
}
