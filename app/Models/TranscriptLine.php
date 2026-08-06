<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranscriptLine extends Model
{
    protected $fillable = [
        'transcript_id', 'semester_id', 'subject_id', 'semester_grade_id',
        'subject_name', 'subject_code', 'credits', 'total_score',
        'letter_grade', 'grade_point', 'traditional_grade', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'grade_point' => 'decimal:2',
        ];
    }

    public function transcript(): BelongsTo
    {
        return $this->belongsTo(Transcript::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
