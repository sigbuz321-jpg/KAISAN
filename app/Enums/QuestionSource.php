<?php

namespace App\Enums;

enum QuestionSource: string
{
    case Manual = 'manual';
    case Ai = 'ai';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Ditulis guru',
            self::Ai => 'Dibuat AI',
        };
    }

    /**
     * Rule 1 of .claude/rules/domain-kaisan.md: an AI question never reaches
     * students without a teacher reading it first, so it cannot skip review.
     */
    public function mayPublishWithoutReview(): bool
    {
        return $this === self::Manual;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
