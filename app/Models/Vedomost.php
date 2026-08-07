<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vedomost extends Model
{
    protected $fillable = [
        'subject_assignment_id',
        'group_id',
        'subject_id',
        'teacher_id',
        'semester_id',
        'academic_year_id',
        'number',
        'exam_date',
        'status',
    ];

    protected $casts = ['exam_date' => 'date'];

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
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

    /**
     * Баҳоҳои донишҷӯёни гурӯҳ барои ин фан
     */
    public function grades(): HasMany
    {
        return $this->hasMany(SemesterGrade::class, 'subject_assignment_id', 'subject_assignment_id')
            ->where('semester_id', $this->semester_id);
    }
}
