<?php

namespace App\Enums;

enum SchoolLevel: string
{
    case SD = 'sd';
    case SMP = 'smp';
    case SMA = 'sma';

    public function label(): string
    {
        return match ($this) {
            self::SD => 'SD',
            self::SMP => 'SMP',
            self::SMA => 'SMA',
        };
    }

    public function defaultStartGrade(): int
    {
        return match ($this) {
            self::SD => 1,
            self::SMP => 7,
            self::SMA => 10,
        };
    }

    public function defaultEndGrade(): int
    {
        return match ($this) {
            self::SD => 6,
            self::SMP => 9,
            self::SMA => 12,
        };
    }

    /** @return list<int> */
    public function grades(): array
    {
        return range($this->defaultStartGrade(), $this->defaultEndGrade());
    }

    /** @return array<int, string> */
    public function gradeOptions(): array
    {
        return collect($this->grades())
            ->mapWithKeys(fn (int $grade) => [$grade => "Kelas {$grade}"])
            ->all();
    }

    public static function fromGrade(?int $grade): ?self
    {
        if ($grade === null) {
            return null;
        }

        return match (true) {
            $grade >= 1 && $grade <= 6 => self::SD,
            $grade >= 7 && $grade <= 9 => self::SMP,
            $grade >= 10 && $grade <= 12 => self::SMA,
            default => null,
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
