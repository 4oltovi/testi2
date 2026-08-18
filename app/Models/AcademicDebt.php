<?php

namespace App\Models;

use App\Enums\DebtStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicDebt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'semester_grade_id',
        'subject_id',
        'semester_id',
        'reason',
        'description',
        'debt_date',
        'original_score',
        'original_grade',
        'retake_allowed',
        'retake_attempts_used',
        'max_retake_attempts',
        'retake_deadline',
        'status',
        'resolved_date',
        'resolved_score',
        'resolved_grade',
        'resolved_by',
        'resolution_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DebtStatus::class,
            'debt_date' => 'date',
            'retake_deadline' => 'date',
            'resolved_date' => 'date',
            'retake_allowed' => 'boolean',
            'original_score' => 'decimal:2',
            'resolved_score' => 'decimal:2',
        ];
    }

    // ==================== РОБИТАҲО ====================

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semesterGrade(): BelongsTo
    {
        return $this->belongsTo(SemesterGrade::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AcademicDebtHistory::class);
    }

    /**
     * Таъиноти фан (агар мавҷуд бошад)
     */
    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class, 'subject_assignment_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', DebtStatus::ACTIVE);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            DebtStatus::ACTIVE,
            DebtStatus::RETAKE_SCHEDULED,
            DebtStatus::ESCALATED,
        ]);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', DebtStatus::RESOLVED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('retake_deadline', '<', now())
            ->whereIn('status', [DebtStatus::ACTIVE, DebtStatus::RETAKE_SCHEDULED]);
    }

    // ==================== МЕТОДҲО ====================

    /**
     * Оё боз кӯшиши такрорсупорӣ дорад?
     */
    public function canRetake(): bool
    {
        return $this->retake_allowed
            && $this->retake_attempts_used < $this->max_retake_attempts
            && $this->status->isOpen()
            && (!$this->retake_deadline || $this->retake_deadline->isFuture());
    }

    /**
     * Ҳал кардани қарздорӣ (баъди гузаштани такрорсупорӣ)
     */
    public function resolve(float $score, string $grade, int $resolvedBy, ?string $note = null): void
    {
        $this->status = DebtStatus::RESOLVED;
        $this->resolved_date = now();
        $this->resolved_score = $score;
        $this->resolved_grade = $grade;
        $this->resolved_by = $resolvedBy;
        $this->resolution_note = $note;
        $this->save();

        // Навсозии ҳолати донишҷӯ
        $activeDebts = static::where('student_id', $this->student_id)->open()->count();
        if ($activeDebts === 0) {
            $this->student->update(['has_debts' => false]);
        }
    }

    /**
     * Оё мӯҳлат гузашт?
     */
    public function isOverdue(): bool
    {
        return $this->retake_deadline && $this->retake_deadline->isPast() && $this->status->isOpen();
    }

    /**
     * Сабаби қарздорӣ ба забони тоҷикӣ
     */
    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            'exam_failed' => 'Имтиҳонро нагузашт',
            'exam_absent' => 'Ба имтиҳон наомад',
            'rating_failed' => 'Рейтинг кам',
            'attendance_low' => 'Давомот кам',
            'not_admitted' => 'Ба имтиҳон иҷозат дода нашуд',
            default => $this->reason,
        };
    }
}
