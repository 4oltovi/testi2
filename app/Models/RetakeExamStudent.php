<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetakeExamStudent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'retake_exam_id',
        'student_id',
        'academic_debt_id',
        'attempt_number',
        'score',
        'letter_grade',
        'status',
        'examiner_id',
        'examined_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'attempt_number' => 'integer',
            'examined_at' => 'datetime',
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

    public function academicDebt(): BelongsTo
    {
        return $this->belongsTo(AcademicDebt::class);
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->status === 'passed';
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }
}
