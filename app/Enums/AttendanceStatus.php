<?php

namespace App\Enums;

/**
 * Ҳолатҳои давомот
 */
enum AttendanceStatus: string
{
    case PRESENT = 'present';   // Ҳозир
    case ABSENT = 'absent';     // Ғоиб
    case EXCUSED = 'excused';   // Ғоиби сабабнок
    case LATE = 'late';         // Дер омада
    case SICK = 'sick';         // Бемор (справка)

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Ҳозир',
            self::ABSENT => 'Ғоиб',
            self::EXCUSED => 'Сабабнок',
            self::LATE => 'Дер',
            self::SICK => 'Бемор',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::PRESENT => '+',
            self::ABSENT => 'Ғ',
            self::EXCUSED => 'С',
            self::LATE => 'Д',
            self::SICK => 'Б',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::PRESENT => 'text-success',
            self::ABSENT => 'text-danger',
            self::EXCUSED => 'text-info',
            self::LATE => 'text-warning',
            self::SICK => 'text-secondary',
        };
    }

    /**
     * Оё "ҳозир" ҳисоб мешавад?
     */
    public function countsAsPresent(): bool
    {
        return in_array($this, [self::PRESENT, self::LATE, self::EXCUSED, self::SICK]);
    }
}
