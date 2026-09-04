<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetakeVedomost extends Model
{
    protected $fillable = [
        'retake_exam_id',
        'group_id',
        'subject_id',
        'teacher_id',
        'semester_id',
        'academic_year_id',
        'number',
        'exam_date',
        'status',
    ];

    protected function casts(): array
    {
        return ['exam_date' => 'date'];
    }

    public function retakeExam(): BelongsTo
    {
        return $this->belongsTo(RetakeExam::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
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
