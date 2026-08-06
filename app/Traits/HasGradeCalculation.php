<?php

namespace App\Traits;

use App\Enums\GradeScale;

/**
 * Trait барои моделҳое, ки ба баҳогузорӣ алоқаманд ҳастанд
 */
trait HasGradeCalculation
{
    /**
     * Гирифтани рангу класси баҳо
     */
    public function getGradeColorAttribute(): string
    {
        if (!$this->letter_grade) return '';

        $grade = GradeScale::tryFrom($this->letter_grade);
        return $grade?->colorClass() ?? '';
    }

    /**
     * Badge class
     */
    public function getGradeBadgeAttribute(): string
    {
        if (!$this->letter_grade) return 'bg-secondary';

        $grade = GradeScale::tryFrom($this->letter_grade);
        return $grade?->badgeClass() ?? 'bg-secondary';
    }

    /**
     * Оё баҳо "гузаштанӣ" аст?
     */
    public function isPassingGrade(): bool
    {
        if (!$this->letter_grade) return false;

        $grade = GradeScale::tryFrom($this->letter_grade);
        return $grade?->isPassing() ?? false;
    }
}
