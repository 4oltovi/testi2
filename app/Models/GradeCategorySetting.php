<?php

namespace App\Models;

use App\Enums\GradeCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeCategorySetting extends Model
{
    protected $fillable = [
        'subject_assignment_id', 'category', 'max_score', 'sort_order', 'is_active',
        'applies_lecture', 'applies_practical', 'applies_lab',
    ];

    protected function casts(): array
    {
        return [
            'category' => GradeCategory::class,
            'is_active' => 'boolean',
            'applies_lecture' => 'boolean',
            'applies_practical' => 'boolean',
            'applies_lab' => 'boolean',
        ];
    }

    /**
     * Оё ин категория барои навъи дарс мувофиқ аст?
     * lesson_type: lecture | practice | lab (маълумоти нодуруст → ҳама мувофиқ).
     */
    public function appliesTo(?string $lessonType): bool
    {
        return match ($lessonType) {
            'lecture' => $this->applies_lecture,
            'practice' => $this->applies_practical,
            'lab' => $this->applies_lab,
            default => true,
        };
    }

    /**
     * Танзимоти мувофиқ барои навъи дарс (барои намоиши саҳифа).
     *
     * @param  \Illuminate\Support\Collection<int, static>  $settings
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function applicableFor($settings, ?string $lessonType)
    {
        return $settings->filter(fn (self $s) => $s->appliesTo($lessonType))->values();
    }

    /**
     * Ҳадди аксари як дарс барои навъи дарс (масалан лексия = 4 категория).
     */
    public static function maxForLessonType($settings, ?string $lessonType): float
    {
        return (float) static::applicableFor($settings, $lessonType)->sum('max_score');
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
                    // Пешфарз: «Савод» барои лексия хомӯш, ҳамаи дигарон фаъол
                    'applies_lecture' => $category !== GradeCategory::SAVOD,
                    'applies_practical' => true,
                    'applies_lab' => true,
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
