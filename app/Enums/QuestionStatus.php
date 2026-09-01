<?php

namespace App\Enums;

enum QuestionStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Review => 'Menunggu tinjauan',
            self::Published => 'Terbit',
            self::Archived => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Review => 'warning',
            self::Published => 'success',
            self::Archived => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Review, self::Published, self::Archived],
            self::Review => [self::Published, self::Draft, self::Archived],
            self::Published => [self::Archived],
            self::Archived => [self::Draft],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }
}
