<?php

namespace App\Models;

use App\Enums\GradeCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryScore extends Model
{
    protected $fillable = [
        'student_id', 'subject_assignment_id', 'semester_id',
        'lesson_date', 'lesson_number', 'category', 'period',
        'score', 'max_score', 'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'category' => GradeCategory::class,
        ];
    }

    // ==================== РОБИТАҲО ====================

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // ==================== SCOPES ====================

    public function scopeForLesson($query, int $subjectAssignmentId, string $date, int $lessonNumber)
    {
        return $query->where('subject_assignment_id', $subjectAssignmentId)
            ->where('lesson_date', $date)
            ->where('lesson_number', $lessonNumber);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeInCategory($query, GradeCategory $category)
    {
        return $query->where('category', $category->value);
    }

    // ==================== АТТРИБУТҲО ====================

    /**
     * Баҳо дар масштоби нормализатсияшуда (0-1)
     */
    public function getNormalizedScoreAttribute(): float
    {
        if ($this->max_score == 0) return 0;
        return round($this->score / $this->max_score, 4);
    }
}
