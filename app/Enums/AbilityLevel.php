<?php

namespace App\Enums;

use App\Services\Adaptive\EloRating;

/**
 * What a student is shown instead of their rating.
 *
 * .claude/skills/adaptive-difficulty is explicit that the raw number never
 * reaches a student: a visible score invites comparison between children, and
 * a bimbel does not need that. A band moves rarely and says something useful
 * about what to do next.
 *
 * The band boundaries are a product decision, not a formula. They are placed so
 * a new student, who starts at 1200, lands in the middle rather than being
 * told they are already advanced.
 */
enum AbilityLevel: string
{
    case Pemula = 'pemula';
    case Berkembang = 'berkembang';
    case Mahir = 'mahir';
    case Ahli = 'ahli';

    public static function forRating(int $rating): self
    {
        return match (true) {
            $rating < 1100 => self::Pemula,
            $rating < 1300 => self::Berkembang,
            $rating < 1550 => self::Mahir,
            default => self::Ahli,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pemula => 'Pemula',
            self::Berkembang => 'Berkembang',
            self::Mahir => 'Mahir',
            self::Ahli => 'Ahli',
        };
    }

    /** Encouraging rather than judging: the student reads this, not a teacher. */
    public function description(): string
    {
        return match ($this) {
            self::Pemula => 'Kamu sedang membangun dasar. Terus berlatih, soalnya akan menyesuaikan.',
            self::Berkembang => 'Kamu sudah menguasai dasarnya dan sedang naik.',
            self::Mahir => 'Kamu menguasai sebagian besar materi di tingkat ini.',
            self::Ahli => 'Kamu konsisten menjawab soal sulit dengan benar.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pemula => 'gray',
            self::Berkembang => 'info',
            self::Mahir => 'success',
            self::Ahli => 'warning',
        };
    }

    /** How far through the whole scale this rating sits, for a progress bar. */
    public static function progressFor(int $rating): int
    {
        $span = EloRating::MAX - EloRating::MIN;

        return (int) round((min(EloRating::MAX, max(EloRating::MIN, $rating)) - EloRating::MIN) / $span * 100);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $l) => [$l->value => $l->label()])
            ->all();
    }
}
