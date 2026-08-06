<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrentGrade extends Model
{
    protected $fillable = [
        'student_id', 'subject_assignment_id', 'semester_id',
        'grade_date', 'week_number', 'grade_type',
        'score', 'max_score', 'comment', 'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'grade_date' => 'date',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
        ];
    }

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

    /**
     * Баҳо дар масштоби 100
     */
    public function getNormalizedScoreAttribute(): float
    {
        if ($this->max_score == 0) return 0;
        return round(($this->score / $this->max_score) * 100, 2);
    }
}
