<?php

namespace App\Models;

use App\Enums\GradeScale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id', 'student_id', 'attempt_number',
        'started_at', 'submitted_at', 'auto_submitted_at',
        'total_score', 'max_possible_score', 'percentage',
        'letter_grade', 'grade_point', 'status',
        'ip_address', 'user_agent', 'disconnections', 'last_activity_at',
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

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * Оё вақт тамом шуд?
     */
    public function isTimeUp(): bool
    {
        $deadline = $this->started_at->addMinutes($this->exam->duration_minutes);
        return now()->isAfter($deadline);
    }

    /**
     * Вақти боқимонда (сония)
     */
    public function getRemainingSecondsAttribute(): int
    {
        $deadline = $this->started_at->addMinutes($this->exam->duration_minutes);
        return max(0, now()->diffInSeconds($deadline, false));
    }

    /**
     * Ҳисоби баҳо
     */
    public function calculateScore(): void
    {
        $totalPoints = $this->answers()->sum('points_earned');
        $maxPoints = $this->exam->examQuestions()->sum('points');

        $this->total_score = $totalPoints;
        $this->max_possible_score = $maxPoints;
        $this->percentage = $maxPoints > 0 ? round(($totalPoints / $maxPoints) * 100, 2) : 0;

        $grade = GradeScale::fromPercentage($this->percentage);
        $this->letter_grade = $grade->value;
        $this->grade_point = $grade->gradePoint();
        $this->status = 'graded';
    }

    /**
     * Супоридан
     */
    public function submit(bool $auto = false): void
    {
        if ($auto) {
            $this->auto_submitted_at = now();
            $this->status = 'auto_submitted';
        } else {
            $this->submitted_at = now();
            $this->status = 'submitted';
        }
        $this->save();
    }

    /**
     * Оё дар ҷараён аст?
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }
}
