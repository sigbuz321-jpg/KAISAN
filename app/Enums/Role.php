<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Guru = 'guru';
    case Murid = 'murid';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Guru => 'Guru',
            self::Murid => 'Murid',
        };
    }

    /**
     * Roles allowed into the Filament panel. Students never reach it;
     * they get their own pages (see .claude/rules/security.md).
     */
    public function canAccessPanel(): bool
    {
        // An allowlist, not a denylist: match without a default throws on a
        // role added later, so a new case fails closed and loudly instead of
        // silently inheriting the run of the teacher panel.
        return match ($this) {
            self::Admin, self::Guru => true,
            self::Murid => false,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
