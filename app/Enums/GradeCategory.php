<?php

namespace App\Enums;

/**
 * Категорияҳои баҳогузорӣ дар журнал
 * Ҳар дарс устод дар ин 5 категория баҳо мегузорад
 */
enum GradeCategory: string
{
    case SAVOD = 'savod';           // Дониш (Knowledge) — ҳамеша баландтарин
    case SARULIBOS = 'sarulibos';   // Сарулибос (Appearance)
    case JIHOZ = 'jihoz';           // Ҷиҳоз (Equipment)
    case ISHTIROK = 'ishtirok';     // Иштирок (Participation)
    case INTIZOM = 'intizom';       // Интизом (Discipline)

    /**
     * Номи тоҷикӣ
     */
    public function label(): string
    {
        return match ($this) {
            self::SAVOD => 'Савод (Дониш)',
            self::SARULIBOS => 'Сарулибос',
            self::JIHOZ => 'Ҷиҳоз',
            self::ISHTIROK => 'Иштирок',
            self::INTIZOM => 'Интизом',
        };
    }

    /**
     * Номи кӯтоҳ
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::SAVOD => 'Савод',
            self::SARULIBOS => 'С/либос',
            self::JIHOZ => 'Ҷиҳоз',
            self::ISHTIROK => 'Иштирок',
            self::INTIZOM => 'Интизом',
        };
    }

    /**
     * Max score пешфарз
     */
    public function defaultMaxScore(): int
    {
        return match ($this) {
            self::SAVOD => 5,       // Баландтарин
            self::SARULIBOS => 2,
            self::JIHOZ => 2,
            self::ISHTIROK => 3,
            self::INTIZOM => 3,
        };
    }

    /**
     * Рангҳо барои UI
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::SAVOD => 'primary',
            self::SARULIBOS => 'info',
            self::JIHOZ => 'warning',
            self::ISHTIROK => 'success',
            self::INTIZOM => 'secondary',
        };
    }

    /**
     * Icon
     */
    public function icon(): string
    {
        return match ($this) {
            self::SAVOD => 'bi-book',
            self::SARULIBOS => 'bi-person-badge',
            self::JIHOZ => 'bi-bag-check',
            self::ISHTIROK => 'bi-hand-index',
            self::INTIZOM => 'bi-shield-check',
        };
    }

    /**
     * Ҳамаи категорияҳо бо тартиб
     */
    public static function ordered(): array
    {
        return [
            self::SAVOD,
            self::SARULIBOS,
            self::JIHOZ,
            self::ISHTIROK,
            self::INTIZOM,
        ];
    }

    /**
     * Ба массив барои JSON/frontend
     */
    public static function toArray(): array
    {
        return array_map(fn(self $cat) => [
            'value' => $cat->value,
            'label' => $cat->label(),
            'short_label' => $cat->shortLabel(),
            'default_max' => $cat->defaultMaxScore(),
            'color' => $cat->colorClass(),
            'icon' => $cat->icon(),
        ], self::ordered());
    }
}
