<?php

namespace App\Models;

use App\Enums\GradeScale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SemesterGrade extends Model
{
    protected $fillable = [
        'student_id', 'subject_assignment_id', 'curriculum_id', 'semester_id',
        'rating1_score', 'rating2_score', 'independent_work_score',
        'exam_score', 'retake_score', 'retake2_score',
        'total_score', 'letter_grade', 'grade_point', 'traditional_grade',
        'credits_earned', 'status',
        'rating1_date', 'rating2_date', 'exam_date', 'retake_date', 'retake2_date',
        'finalized_at', 'exam_teacher_id', 'finalized_by', 'is_finalized',
    ];

    protected function casts(): array
    {
        return [
            'rating1_score' => 'decimal:2',
            'rating2_score' => 'decimal:2',
            'independent_work_score' => 'decimal:2',
            'exam_score' => 'decimal:2',
            'retake_score' => 'decimal:2',
            'retake2_score' => 'decimal:2',
            'total_score' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'is_finalized' => 'boolean',
            'rating1_date' => 'datetime',
            'rating2_date' => 'datetime',
            'exam_date' => 'datetime',
            'retake_date' => 'datetime',
            'retake2_date' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    // ==================== РОБИТАҲО ====================

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function examTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exam_teacher_id');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function changeLog(): HasMany
    {
        return $this->hasMany(GradeChangeLog::class);
    }

    public function academicDebt(): HasOne
    {
        return $this->hasOne(AcademicDebt::class, 'semester_grade_id');
    }

    // ==================== SCOPES ====================

    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'debt']);
    }

    public function scopeFinalized($query)
    {
        return $query->where('is_finalized', true);
    }

    public function scopeInSemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    // ==================== МЕТОДҲО ====================

    /**
     * Ҳисоби баҳои ниҳоӣ мувофиқи логикаи барнома
     *
     * Формула:
     * total_score = ((rating + journal) / 2) + (exam × 0.50)
     *
     * Масалан: rating = 40, journal = 35, exam = 70
     * total = ((40 + 35) / 2) + (70 × 0.50) = 37.5 + 35 = 72.5
     */
    public function calculateTotalScore(): ?float
    {
        $examScore = $this->retake2_score ?? $this->retake_score ?? $this->exam_score;

        $ratingScore = $this->rating1_score;
        $journalScore = $this->rating2_score ?? $this->independent_work_score ?? null;

        if (is_null($ratingScore) || is_null($journalScore) || is_null($examScore)) {
            return null;
        }

        $combinedScore = (($ratingScore + $journalScore) / 2);
        $total = $combinedScore + ($examScore * 0.50);

        return round($total, 2);
    }

    /**
     * Ҳисоб ва сабти баҳои ниҳоӣ
     */
    public function calculateAndSetFinalGrade(): void
    {
        $totalScore = $this->calculateTotalScore();

        if (is_null($totalScore)) {
            return;
        }

        $grade = GradeScale::fromPercentage($totalScore);

        $this->total_score = $totalScore;
        $this->letter_grade = $grade->value;
        $this->grade_point = $grade->gradePoint();
        $this->traditional_grade = $grade->traditionalGrade();

        if ($grade->isPassing()) {
            $this->status = 'passed';
            $this->credits_earned = $this->curriculum?->credits ?? 0;
        } else {
            $this->status = $grade->canRetake() ? 'retake' : 'failed';
            $this->credits_earned = 0;
        }
    }

    /**
     * Оё гузашт?
     */
    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    /**
     * Оё баҳои ниҳоӣ тасдиқ шудааст?
     */
    public function isFinalized(): bool
    {
        return $this->is_finalized;
    }

    /**
     * GradeScale enum
     */
    public function getGradeEnumAttribute(): ?GradeScale
    {
        if (!$this->letter_grade) return null;
        return GradeScale::tryFrom($this->letter_grade);
    }
}
