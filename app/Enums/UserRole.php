<?php

namespace App\Enums;

/**
 * Нақшҳои корбарон
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';       // Администратори аввал
    case ADMIN = 'admin';                   // Администратор
    case DEAN = 'dean';                     // Декан
    case VICE_DEAN = 'vice_dean';           // Муовини декан
    case DEPARTMENT_HEAD = 'department_head'; // Мудири кафедра
    case REGISTRAR = 'registrar';           // Бақайдгир
    case TEACHER = 'teacher';               // Омӯзгор
    case ACCOUNTANT = 'accountant';         // Муҳосиб
    case STUDENT = 'student';               // Донишҷӯ
    case OPERATOR = 'operator';             // Оператор/Контролёр

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Суперадмин',
            self::ADMIN => 'Администратор',
            self::DEAN => 'Декан',
            self::VICE_DEAN => 'Муовини декан',
            self::DEPARTMENT_HEAD => 'Мудири кафедра',
            self::REGISTRAR => 'Бақайдгир',
            self::TEACHER => 'Омӯзгор',
            self::ACCOUNTANT => 'Муҳосиб',
            self::STUDENT => 'Донишҷӯ',
            self::OPERATOR => 'Оператор',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 100,
            self::ADMIN => 90,
            self::DEAN => 80,
            self::VICE_DEAN => 75,
            self::DEPARTMENT_HEAD => 70,
            self::REGISTRAR => 60,
            self::TEACHER => 50,
            self::ACCOUNTANT => 40,
            self::STUDENT => 10,
            self::OPERATOR => 30,
        };
    }

    /**
     * Модулҳои дастрас барои ин нақш
     */
    public function allowedModules(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => ['*'], // Ҳамаи модулҳо
            self::ADMIN => ['users', 'structure', 'students', 'teachers', 'journal', 'ratings', 'exams', 'debts', 'transcript', 'reports', 'audit'],
            self::DEAN => ['students', 'teachers', 'journal', 'ratings', 'exams', 'debts', 'transcript', 'reports'],
            self::VICE_DEAN => ['students', 'teachers', 'journal', 'ratings', 'exams', 'debts', 'reports'],
            self::DEPARTMENT_HEAD => ['teachers', 'journal', 'ratings', 'exams', 'debts', 'reports'],
            self::REGISTRAR => ['structure', 'students', 'teachers', 'debts', 'transcript', 'reports'],
            self::TEACHER => ['journal', 'exams', 'ratings'],
            self::ACCOUNTANT => ['students', 'debts', 'reports'],
            self::STUDENT => ['my_grades', 'my_exams', 'my_transcript'],
            self::OPERATOR => ['students', 'journal', 'ratings', 'reports'],
        };
    }
}
