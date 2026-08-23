<?php

namespace App\Enums;

enum OrphanType: string
{
    case NONE = 'none';
    case ORPHAN = 'orphan';
    case HALF_ORPHAN = 'half_orphan';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Не',
            self::ORPHAN => 'Ятим',
            self::HALF_ORPHAN => 'Ниятим',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NONE => 'bg-secondary',
            self::ORPHAN => 'bg-danger',
            self::HALF_ORPHAN => 'bg-warning',
        };
    }
}
