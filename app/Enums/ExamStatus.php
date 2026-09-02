<?php

namespace App\Enums;

/**
 * draft → scheduled → active → closed → graded
 *
 * A scheduled command walks exams through these every minute. The status is
 * never derived on the fly during a request: 150 students refreshing an exam
 * page must not each recompute it.
 */
enum ExamStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Closed = 'closed';
    case Graded = 'graded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Scheduled => 'Terjadwal',
            self::Active => 'Sedang berlangsung',
            self::Closed => 'Ditutup',
            self::Graded => 'Sudah dinilai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'info',
            self::Active => 'success',
            self::Closed => 'warning',
            self::Graded => 'primary',
        };
    }

    /** Students may see an exam only once it has left the teacher's desk. */
    public function isVisibleToStudents(): bool
    {
        return $this !== self::Draft;
    }

    public function acceptsSubmissions(): bool
    {
        return $this === self::Active;
    }

    /**
     * Once a single student can see the questions, the paper is fixed. A
     * teacher who needs different questions creates a new exam -- rule 3 of
     * .claude/rules/domain-kaisan.md.
     */
    public function allowsQuestionEditing(): bool
    {
        return $this === self::Draft;
    }

    public function hasFinished(): bool
    {
        return $this === self::Closed || $this === self::Graded;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
