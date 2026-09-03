<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One answer during practice, with the rating either side of it.
 *
 * Keeping both ratings means a teacher can explain to a parent exactly how a
 * student's level moved and which question moved it, months later.
 *
 * @property int $id
 * @property int $practice_session_id
 * @property int $question_id
 * @property string $selected_option
 * @property bool $is_correct
 * @property int $rating_before
 * @property int $rating_after
 * @property Carbon $answered_at
 */
class PracticeAnswer extends Model
{
    protected $fillable = [
        'practice_session_id',
        'question_id',
        'selected_option',
        'is_correct',
        'rating_before',
        'rating_after',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'rating_before' => 'integer',
            'rating_after' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PracticeSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'practice_session_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
