<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingAttempt extends Model
{
    protected $fillable = [
        'rating_session_id',
        'student_id',
        'subject_id',
        'attempt_number',
        'started_at',
        'finished_at',
        'correct_count',
        'total_questions',
        'percentage',
        'status',
        'answers_json',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'answers_json' => 'array',
            'percentage' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RatingSession::class, 'rating_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
