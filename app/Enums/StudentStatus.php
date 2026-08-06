<?php

namespace App\Enums;

/**
 * Ҳолатҳои донишҷӯ
 */
enum StudentStatus: string
{
    case ACTIVE = 'active';                 // Фаъол
    case ACADEMIC_LEAVE = 'academic_leave'; // Рухсатии академӣ
    case EXPELLED = 'expelled';             // Хориҷшуда
    case GRADUATED = 'graduated';           // Хатмкарда
    case TRANSFERRED = 'transferred';       // Гузаронидашуда
    case RESTORED = 'restored';             // Барқароршуда
    case SUSPENDED = 'suspended';           // Муваққатан боздошташуда

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Фаъол',
            self::ACADEMIC_LEAVE => 'Рухсатии академӣ',
            self::EXPELLED => 'Хориҷшуда',
            self::GRADUATED => 'Хатмкарда',
            self::TRANSFERRED => 'Гузаронидашуда',
            self::RESTORED => 'Барқароршуда',
            self::SUSPENDED => 'Боздошташуда',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-success',
            self::ACADEMIC_LEAVE => 'bg-info',
            self::EXPELLED => 'bg-danger',
            self::GRADUATED => 'bg-primary',
            self::TRANSFERRED => 'bg-secondary',
            self::RESTORED => 'bg-warning',
            self::SUSPENDED => 'bg-dark',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
