<?php

namespace App\Models;

use App\Enums\AbilityLevel;
use App\Services\Adaptive\EloRating;
use Database\Factories\StudentAbilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * How strong one student is in one subject.
 *
 * @property int $id
 * @property int $user_id
 * @property int $subject_id
 * @property int $rating
 * @property int $answers_count
 * @property Carbon|null $last_practiced_at
 * @property-read User $student
 * @property-read Subject $subject
 */
class StudentAbility extends Model
{
    /** @use HasFactory<StudentAbilityFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'subject_id', 'rating', 'answers_count', 'last_practiced_at'];

    /**
     * Mirrors the column defaults so a freshly created model reports them
     * rather than null. See coding-style.md.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'rating' => EloRating::START,
        'answers_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'answers_count' => 'integer',
            'last_practiced_at' => 'datetime',
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

    /** The band a student is shown. The rating itself never reaches them. */
    public function level(): AbilityLevel
    {
        return AbilityLevel::forRating($this->rating);
    }

    public function progress(): int
    {
        return AbilityLevel::progressFor($this->rating);
    }

    /** The rating a student starts a subject on, before answering anything. */
    public static function startingRating(): int
    {
        return EloRating::START;
    }
}
