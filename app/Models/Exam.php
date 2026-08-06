<?php

namespace App\Models;

use App\Enums\ExamType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_assignment_id', 'semester_id', 'teacher_id', 'group_id',
        'title', 'description', 'exam_type', 'format',
        'duration_minutes', 'total_questions_count', 'passing_score',
        'shuffle_questions', 'shuffle_answers', 'show_results_immediately',
        'allow_back_navigation', 'max_attempts', 'auto_save',
        'starts_at', 'ends_at', 'status', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results_immediately' => 'boolean',
            'allow_back_navigation' => 'boolean',
            'auto_save' => 'boolean',
            'is_published' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'passing_score' => 'decimal:2',
        ];
    }

    // ==================== РОБИТАҲО ====================

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot(['sort_order', 'points'])
            ->orderByPivot('sort_order');
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeAvailableNow($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    // ==================== МЕТОДҲО ====================

    /**
     * Оё имтиҳон ҳозир дастрас аст?
     */
    public function isAvailable(): bool
    {
        return $this->status === 'active'
            && $this->starts_at?->isPast()
            && $this->ends_at?->isFuture();
    }

    /**
     * Оё вақт тамом шудааст?
     */
    public function isExpired(): bool
    {
        return $this->ends_at?->isPast();
    }

    /**
     * Шумораи кӯшишҳои донишҷӯ
     */
    public function getAttemptsCountForStudent(int $studentId): int
    {
        return $this->attempts()->where('student_id', $studentId)->count();
    }

    /**
     * Оё донишҷӯ ҳоло метавонад имтиҳон диҳад?
     */
    public function canStudentAttempt(int $studentId): bool
    {
        if (!$this->isAvailable()) return false;

        $attempts = $this->getAttemptsCountForStudent($studentId);
        return $attempts < $this->max_attempts;
    }

    /**
     * Вақти боқимонда (дақиқа)
     */
    public function getRemainingMinutesAttribute(): int
    {
        if (!$this->ends_at) return $this->duration_minutes;
        return max(0, now()->diffInMinutes($this->ends_at, false));
    }
}
