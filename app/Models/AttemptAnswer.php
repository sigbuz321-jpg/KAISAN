<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One answer inside one attempt.
 *
 * `is_correct` is written by the server at grading time and never comes from
 * the browser -- see App\Services\Scoring\ScoreCalculator.
 *
 * @property int $id
 * @property int $exam_attempt_id
 * @property int $question_id
 * @property string|null $selected_option
 * @property bool|null $is_correct
 * @property Carbon $answered_at
 * @property int|null $time_spent_ms
 * @property-read ExamAttempt $attempt
 * @property-read Question $question
 */
class AttemptAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'selected_option',
        'answered_at',
        'time_spent_ms',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
            'time_spent_ms' => 'integer',
        ];
    }

    /** @return BelongsTo<ExamAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
