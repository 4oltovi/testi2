<?php

namespace App\Models;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotion extends Model
{
    protected $fillable = [
        'student_id', 'from_group_id', 'to_group_id',
        'from_course_id', 'to_course_id', 'academic_year_id',
        'order_number', 'order_date', 'gpa_at_promotion', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'gpa_at_promotion' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'from_group_id');
    }

    public function toGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'to_group_id');
    }

    public function fromCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'from_course_id');
    }

    public function toCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'to_course_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
