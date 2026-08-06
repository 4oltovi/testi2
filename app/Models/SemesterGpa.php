<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterGpa extends Model
{
    protected $fillable = [
        'student_id', 'semester_id', 'academic_year_id',
        'gpa', 'credits_attempted', 'credits_earned',
        'subjects_passed', 'subjects_failed', 'total_grade_points',
        'total_subjects', 'cumulative_gpa', 'cumulative_credits_earned',
        'is_finalized', 'finalized_at', 'finalized_by',
    ];

    protected function casts(): array
    {
        return [
            'gpa' => 'decimal:2',
            'total_grade_points' => 'decimal:2',
            'cumulative_gpa' => 'decimal:2',
            'is_finalized' => 'boolean',
            'finalized_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
