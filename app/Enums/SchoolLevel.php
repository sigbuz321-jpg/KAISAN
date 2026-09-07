<?php

namespace App\Enums;

enum SchoolLevel: string
{
    case SD = 'sd';
    case SMP = 'smp';

    public function label(): string
    {
        return match ($this) {
            self::SD => 'SD',
            self::SMP => 'SMP',
        };
    }

    public function defaultStartGrade(): int
    {
        return match ($this) {
            self::SD => 1,
            self::SMP => 7,
        };
    }

    public function defaultEndGrade(): int
    {
        return match ($this) {
            self::SD => 6,
            self::SMP => 9,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $level) => [$level->value => $level->label()])
            ->all();
    }
}
