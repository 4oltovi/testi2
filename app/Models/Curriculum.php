<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    protected $table = 'curriculum';

    protected $fillable = [
        'specialty_id', 'subject_id', 'course_id', 'semester_id',
        'credits', 'total_hours', 'lecture_hours', 'practice_hours',
        'lab_hours', 'independent_hours', 'exam_type', 'control_type',
        'is_elective', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_elective' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    public function semesterGrades(): HasMany
    {
        return $this->hasMany(SemesterGrade::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
