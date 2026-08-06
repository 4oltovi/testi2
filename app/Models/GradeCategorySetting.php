<?php

namespace App\Models;

use App\Enums\GradeCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeCategorySetting extends Model
{
    protected $fillable = [
        'subject_assignment_id', 'category', 'max_score', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => GradeCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    /**
     * Гирифтани танзимоти категорияҳо барои як subject_assignment
     * Агар нест — default-ро месозад
     */
    public static function getOrCreateDefaults(int $subjectAssignmentId): \Illuminate\Database\Eloquent\Collection
    {
        $existing = static::where('subject_assignment_id', $subjectAssignmentId)->get();

        if ($existing->count() >= 5) {
            return $existing->sortBy('sort_order');
        }

        // Default-ҳоро месозем
        foreach (GradeCategory::ordered() as $index => $category) {
            static::firstOrCreate(
                [
                    'subject_assignment_id' => $subjectAssignmentId,
                    'category' => $category->value,
                ],
                [
                    'max_score' => $category->defaultMaxScore(),
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }

        return static::where('subject_assignment_id', $subjectAssignmentId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Маҷмӯи ҳамаи max_score-ҳо (барои як дарс)
     */
    public static function getTotalMaxScore(int $subjectAssignmentId): int
    {
        return static::where('subject_assignment_id', $subjectAssignmentId)
            ->where('is_active', true)
            ->sum('max_score');
    }
}
