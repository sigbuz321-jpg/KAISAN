<?php

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A ranking period. Resetting one clears the leaderboard and nothing else --
 * exam results and student ratings outlive every season.
 *
 * @property int $id
 * @property string $name
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 */
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    protected $fillable = ['name', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Exam, $this> */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * The season new exams belong to. A partial unique index guarantees there
     * is at most one, so this can never be ambiguous.
     */
    public static function current(): ?self
    {
        return self::query()->where('is_active', true)->first();
    }

    /** @param Builder<Season> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
