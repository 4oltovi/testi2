<?php

namespace App\Enums;

/**
 * Ҳолатҳои қарздории академӣ
 */
enum DebtStatus: string
{
    case ACTIVE = 'active';                     // Қарз мавҷуд аст
    case RETAKE_SCHEDULED = 'retake_scheduled'; // Такрорсупорӣ таъин шуд
    case RESOLVED = 'resolved';                 // Ҳал шуд
    case ESCALATED = 'escalated';               // Ба комиссия фиристода шуд
    case REPEAT_COURSE = 'repeat_course';       // Бояд дубора хонад
    case EXPELLED = 'expelled';                 // Хориҷ шуд

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Фаъол',
            self::RETAKE_SCHEDULED => 'Такрорсупорӣ таъин шуд',
            self::RESOLVED => 'Ҳал шуд',
            self::ESCALATED => 'Ба комиссия',
            self::REPEAT_COURSE => 'Дубора хондан',
            self::EXPELLED => 'Хориҷшуда',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-danger',
            self::RETAKE_SCHEDULED => 'bg-warning',
            self::RESOLVED => 'bg-success',
            self::ESCALATED => 'bg-info',
            self::REPEAT_COURSE => 'bg-secondary',
            self::EXPELLED => 'bg-dark',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::ACTIVE, self::RETAKE_SCHEDULED, self::ESCALATED]);
    }
}
