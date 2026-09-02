<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang dikerjakan',
            self::Submitted => 'Sudah dikumpulkan',
            self::Voided => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'warning',
            self::Submitted => 'success',
            self::Voided => 'danger',
        };
    }

    /**
     * Only a submitted, unvoided attempt earns leaderboard points. A voided
     * result keeps its score on the record -- rule 6 of domain-kaisan.md says
     * results are never deleted -- but it stops counting.
     */
    public function countsTowardsRanking(): bool
    {
        return $this === self::Submitted;
    }
}
