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
    case HR = 'hr';                         // Кадр (кадр ходим)
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
            self::HR => 'Кадр',
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
            self::HR => 60,
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
            self::HR => ['hr'],
            self::TEACHER => ['journal', 'exams', 'ratings'],
            self::ACCOUNTANT => ['students', 'debts', 'reports'],
            self::STUDENT => ['my_grades', 'my_exams', 'my_transcript'],
            self::OPERATOR => ['students', 'journal', 'ratings', 'reports'],
        };
    }

    /**
     * Нақшҳое, ки «корманд» ҳисоб мешаванд — яъне корбарони корӣ.
     *
     * Дар ин рӯйхат суперадмин, админ ва донишҷӯ НЕ ҳастанд: онҳо
     * корманд нестанд ва Кадр супориши идоракунии онҳоро надорад.
     * Ин рӯйхат дар чойҳои муҳим (HR pages, tests, сиёсатҳо) истифода мешавад
     * то таърифи «корманд» дар як ҷо шавад.
     *
     * @return array<int, string> slug-ҳои нақшҳо
     */
    public static function employeeRoles(): array
    {
        return [
            self::TEACHER->value,
            self::DEAN->value,
            self::VICE_DEAN->value,
            self::DEPARTMENT_HEAD->value,
            self::ACCOUNTANT->value,
            self::OPERATOR->value,
            self::HR->value,
        ];
    }

    /**
     * Оё ин нақш «корманд» аст (корбари корӣ)?
     */
    public function isEmployee(): bool
    {
        return in_array($this->value, self::employeeRoles(), true);
    }

    /**
     * Нақшҳое, ки Кадр метавонад ба корманд диҳад. Админ, суперадмин ва
     * донишҷӯ дар ин рӯйхат НЕ ҳастанд.
     *
     * @return array<int, string>
     */
    public static function assignableEmployeeRoles(): array
    {
        return self::employeeRoles();
    }
}
