<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetakeExamAnswer extends Model
{
    protected $fillable = [
        'retake_exam_attempt_id',
        'retake_exam_question_id',
        'question_id',
        'selected_options',
        'text_answer',
        'is_correct',
        'points_earned',
        'teacher_comment',
        'is_graded',
        'answered_at',
        'is_flagged',
    ];

    protected function casts(): array
    {
        return [
            'selected_options' => 'json',
            'is_correct' => 'boolean',
            'is_graded' => 'boolean',
            'is_flagged' => 'boolean',
            'points_earned' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function retakeExamAttempt(): BelongsTo
    {
        return $this->belongsTo(RetakeExamAttempt::class);
    }

    public function retakeExamQuestion(): BelongsTo
    {
        return $this->belongsTo(RetakeExamQuestion::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
