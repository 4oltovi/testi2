<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeChangeLog extends Model
{
    protected $table = 'grade_change_log';

    protected $fillable = [
        'semester_grade_id', 'student_id', 'field_changed',
        'old_value', 'new_value', 'reason', 'changed_by', 'ip_address',
    ];

    public function semesterGrade(): BelongsTo
    {
        return $this->belongsTo(SemesterGrade::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
