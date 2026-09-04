<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One student's standing in one season, either overall or in one subject.
 *
 * A null subject_id means the combined board. Written only by
 * LeaderboardCalculator; nothing else should insert here.
 *
 * @property int $id
 * @property int $season_id
 * @property int|null $subject_id
 * @property int $user_id
 * @property string $points
 * @property int $rank
 * @property Carbon $computed_at
 */
class LeaderboardEntry extends Model
{
    protected $fillable = ['season_id', 'subject_id', 'user_id', 'points', 'rank', 'computed_at'];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'computed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isCombined(): bool
    {
        return $this->subject_id === null;
    }

    /** @param Builder<LeaderboardEntry> $query */
    public function scopeCombined(Builder $query): void
    {
        $query->whereNull('subject_id');
    }

    /** @param Builder<LeaderboardEntry> $query */
    public function scopeForSubject(Builder $query, int $subjectId): void
    {
        $query->where('subject_id', $subjectId);
    }
}
