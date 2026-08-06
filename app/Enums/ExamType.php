<?php

namespace App\Enums;

/**
 * Навъҳои имтиҳон
 */
enum ExamType: string
{
    case MAIN = 'main';                         // Имтиҳони асосӣ
    case RETAKE = 'retake';                     // Такрорсупорӣ
    case RETAKE_COMMISSION = 'retake_commission'; // Комиссионӣ
    case RATING1 = 'rating1';                   // Рейтинги 1
    case RATING2 = 'rating2';                   // Рейтинги 2
    case MIDTERM = 'midterm';                   // Миёнасеместрӣ
    case QUIZ = 'quiz';                         // Тести кӯтоҳ

    public function label(): string
    {
        return match ($this) {
            self::MAIN => 'Имтиҳони асосӣ',
            self::RETAKE => 'Такрорсупорӣ',
            self::RETAKE_COMMISSION => 'Комиссионӣ',
            self::RATING1 => 'Рейтинги 1',
            self::RATING2 => 'Рейтинги 2',
            self::MIDTERM => 'Миёнасеместрӣ',
            self::QUIZ => 'Тести кӯтоҳ',
        };
    }
}
