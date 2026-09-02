<?php

namespace App\Enums;

enum AiJobStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Menunggu antrean',
            self::Running => 'Sedang dibuat',
            self::Done => 'Selesai',
            self::Failed => 'Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Running => 'info',
            self::Done => 'success',
            self::Failed => 'danger',
        };
    }

    /** A job that has stopped, either way, is never picked up again. */
    public function isFinished(): bool
    {
        return $this === self::Done || $this === self::Failed;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
