<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetakeExamQuestion extends Model
{
    protected $fillable = [
        'retake_exam_id',
        'question_id',
        'sort_order',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
        ];
    }

    public function retakeExam(): BelongsTo
    {
        return $this->belongsTo(RetakeExam::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
