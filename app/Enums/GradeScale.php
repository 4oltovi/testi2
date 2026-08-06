<?php

namespace App\Enums;

/**
 * Шкалаи баҳогузории низоми кредитии Тоҷикистон
 * Мутобиқ ба Низомномаи низоми кредитии таҳсилот дар муассисаҳои
 * таҳсилоти олии касбии Ҷумҳурии Тоҷикистон
 */
enum GradeScale: string
{
    case A = 'A';       // 95-100, GPA 4.0, Аъло
    case A_MINUS = 'A-';   // 90-94, GPA 3.67, Аъло
    case B_PLUS = 'B+';    // 85-89, GPA 3.33, Хуб
    case B = 'B';       // 80-84, GPA 3.0, Хуб
    case B_MINUS = 'B-';   // 75-79, GPA 2.67, Хуб
    case C_PLUS = 'C+';    // 70-74, GPA 2.33, Қаноатбахш
    case C = 'C';       // 65-69, GPA 2.0, Қаноатбахш
    case C_MINUS = 'C-';   // 60-64, GPA 1.67, Қаноатбахш
    case D_PLUS = 'D+';    // 55-59, GPA 1.33, Қаноатбахш
    case D = 'D';       // 50-54, GPA 1.0, Қаноатбахш
    case FX = 'Fx';     // 45-49, GPA 0, Ғайриқаноатбахш (имкони такрорсупорӣ)
    case F = 'F';       // 0-44, GPA 0, Ғайриқаноатбахш (дубора хондан)

    /**
     * Гирифтани баҳои ҳарфӣ аз рӯйи фоиз
     */
    public static function fromPercentage(float $percentage): self
    {
        return match (true) {
            $percentage >= 95 => self::A,
            $percentage >= 90 => self::A_MINUS,
            $percentage >= 85 => self::B_PLUS,
            $percentage >= 80 => self::B,
            $percentage >= 75 => self::B_MINUS,
            $percentage >= 70 => self::C_PLUS,
            $percentage >= 65 => self::C,
            $percentage >= 60 => self::C_MINUS,
            $percentage >= 55 => self::D_PLUS,
            $percentage >= 50 => self::D,
            $percentage >= 45 => self::FX,
            default => self::F,
        };
    }

    /**
     * Гирифтани GPA (Grade Point) аз рӯйи баҳо
     */
    public function gradePoint(): float
    {
        return match ($this) {
            self::A => 4.0,
            self::A_MINUS => 3.67,
            self::B_PLUS => 3.33,
            self::B => 3.0,
            self::B_MINUS => 2.67,
            self::C_PLUS => 2.33,
            self::C => 2.0,
            self::C_MINUS => 1.67,
            self::D_PLUS => 1.33,
            self::D => 1.0,
            self::FX => 0.0,
            self::F => 0.0,
        };
    }

    /**
     * Гирифтани ифодаи анъанавии баҳо
     */
    public function traditionalGrade(): string
    {
        return match ($this) {
            self::A, self::A_MINUS => 'Аъло',
            self::B_PLUS, self::B, self::B_MINUS => 'Хуб',
            self::C_PLUS, self::C, self::C_MINUS, self::D_PLUS, self::D => 'Қаноатбахш',
            self::FX, self::F => 'Ғайриқаноатбахш',
        };
    }

    /**
     * Оё донишҷӯ гузашт? (ҳадди ақал D = 50%)
     */
    public function isPassing(): bool
    {
        return match ($this) {
            self::A, self::A_MINUS,
            self::B_PLUS, self::B, self::B_MINUS,
            self::C_PLUS, self::C, self::C_MINUS,
            self::D_PLUS, self::D => true,
            self::FX, self::F => false,
        };
    }

    /**
     * Оё имкони такрорсупорӣ дорад?
     * Fx = Имкони такрорсупорӣ (retake)
     * F = Бояд дубора хонад (repeat course)
     */
    public function canRetake(): bool
    {
        return $this === self::FX;
    }

    /**
     * Оё бояд фанро дубора хонад?
     */
    public function mustRepeatCourse(): bool
    {
        return $this === self::F;
    }

    /**
     * Ҳудуди поёнӣ ва болоии фоиз
     */
    public function percentageRange(): array
    {
        return match ($this) {
            self::A => ['min' => 95, 'max' => 100],
            self::A_MINUS => ['min' => 90, 'max' => 94.99],
            self::B_PLUS => ['min' => 85, 'max' => 89.99],
            self::B => ['min' => 80, 'max' => 84.99],
            self::B_MINUS => ['min' => 75, 'max' => 79.99],
            self::C_PLUS => ['min' => 70, 'max' => 74.99],
            self::C => ['min' => 65, 'max' => 69.99],
            self::C_MINUS => ['min' => 60, 'max' => 64.99],
            self::D_PLUS => ['min' => 55, 'max' => 59.99],
            self::D => ['min' => 50, 'max' => 54.99],
            self::FX => ['min' => 45, 'max' => 49.99],
            self::F => ['min' => 0, 'max' => 44.99],
        };
    }

    /**
     * Номи пурраи баҳо барои интерфейс
     */
    public function label(): string
    {
        return match ($this) {
            self::A => 'A (Аъло, 95-100)',
            self::A_MINUS => 'A- (Аъло, 90-94)',
            self::B_PLUS => 'B+ (Хуб, 85-89)',
            self::B => 'B (Хуб, 80-84)',
            self::B_MINUS => 'B- (Хуб, 75-79)',
            self::C_PLUS => 'C+ (Қаноатбахш, 70-74)',
            self::C => 'C (Қаноатбахш, 65-69)',
            self::C_MINUS => 'C- (Қаноатбахш, 60-64)',
            self::D_PLUS => 'D+ (Қаноатбахш, 55-59)',
            self::D => 'D (Қаноатбахш, 50-54)',
            self::FX => 'Fx (Ғайриқаноатбахш, 45-49) — Такрорсупорӣ',
            self::F => 'F (Ғайриқаноатбахш, 0-44) — Дубора хондан',
        };
    }

    /**
     * Рангҳо барои интерфейс (CSS class)
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::A, self::A_MINUS => 'text-success',
            self::B_PLUS, self::B, self::B_MINUS => 'text-primary',
            self::C_PLUS, self::C, self::C_MINUS, self::D_PLUS, self::D => 'text-warning',
            self::FX, self::F => 'text-danger',
        };
    }

    /**
     * Badge class барои интерфейс
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::A, self::A_MINUS => 'bg-success',
            self::B_PLUS, self::B, self::B_MINUS => 'bg-primary',
            self::C_PLUS, self::C, self::C_MINUS, self::D_PLUS, self::D => 'bg-warning',
            self::FX => 'bg-danger',
            self::F => 'bg-dark',
        };
    }

    /**
     * Ҳамаи баҳоҳо ба массив (барои dropdown/select)
     */
    public static function toArray(): array
    {
        return array_map(fn(self $grade) => [
            'value' => $grade->value,
            'grade_point' => $grade->gradePoint(),
            'traditional' => $grade->traditionalGrade(),
            'label' => $grade->label(),
            'is_passing' => $grade->isPassing(),
            'range' => $grade->percentageRange(),
        ], self::cases());
    }
}
