<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetakeExamAttempt extends Model
{
    protected $fillable = [
        'retake_exam_id',
        'student_id',
        'retake_exam_student_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'auto_submitted_at',
        'total_score',
        'max_possible_score',
        'percentage',
        'letter_grade',
        'grade_point',
        'status',
        'ip_address',
        'user_agent',
        'disconnections',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'auto_submitted_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'total_score' => 'decimal:2',
            'max_possible_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'grade_point' => 'decimal:2',
        ];
    }

    public function retakeExam(): BelongsTo
    {
        return $this->belongsTo(RetakeExam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function retakeExamStudent(): BelongsTo
    {
        return $this->belongsTo(RetakeExamStudent::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RetakeExamAnswer::class);
    }
}
