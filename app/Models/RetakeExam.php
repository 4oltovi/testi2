<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetakeExam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'semester_id',
        'teacher_id',
        'main_exam_id',
        'title',
        'description',
        'format',
        'duration_minutes',
        'passing_score',
        'max_attempts',
        'exam_date',
        'retake_type',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'decimal:2',
            'duration_minutes' => 'integer',
            'max_attempts' => 'integer',
            'exam_date' => 'date',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mainExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'main_exam_id');
    }

    public function retakeExamStudents(): HasMany
    {
        return $this->hasMany(RetakeExamStudent::class);
    }

    public function retakeVedomosts(): HasMany
    {
        return $this->hasMany(RetakeVedomost::class);
    }

    public function getStudentsCountAttribute(): int
    {
        return $this->retakeExamStudents()->count();
    }

    public function getPassedCountAttribute(): int
    {
        return $this->retakeExamStudents()->where('status', 'passed')->count();
    }

    public function getFailedCountAttribute(): int
    {
        return $this->retakeExamStudents()->where('status', 'failed')->count();
    }
}
