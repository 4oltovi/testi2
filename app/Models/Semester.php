<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'academic_year_id', 'number', 'name', 'start_date', 'end_date',
        'exam_start_date', 'exam_end_date', 'retake_start_date', 'retake_end_date',
        'is_current', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'exam_start_date' => 'date',
            'exam_end_date' => 'date',
            'retake_start_date' => 'date',
            'retake_end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function curriculum(): HasMany
    {
        return $this->hasMany(Curriculum::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    public function isExamPeriod(): bool
    {
        return $this->status === 'exam_period';
    }

    public function isRetakePeriod(): bool
    {
        return $this->status === 'retake_period';
    }
}
