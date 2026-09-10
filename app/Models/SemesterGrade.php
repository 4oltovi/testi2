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
        'student_id',
        'subject_assignment_id',
        'semester_id',
        'rating1_score',
        'rating2_score',
        'exam_score',
        'retake_score',
        'total_score',
        'letter_grade',
        'grade_point',
        'traditional_grade',
        'credits_earned',
        'status',
        'rating1_date',
        'rating2_date',
        'exam_date',
        'retake_date',
        'finalized_at',
        'exam_teacher_id',
        'finalized_by',
        'is_finalized',
    ];

    protected function casts(): array
    {
        return [
            'rating1_score' => 'decimal:2',
            'rating2_score' => 'decimal:2',
            'exam_score' => 'decimal:2',
            'retake_score' => 'decimal:2',
            'total_score' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'is_finalized' => 'boolean',
            'rating1_date' => 'datetime',
            'rating2_date' => 'datetime',
            'exam_date' => 'datetime',
            'retake_date' => 'datetime',
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

    /**
     * Фан (shortcut)
     */
    public function getSubjectAttribute(): ?Subject
    {
        return $this->subjectAssignment?->subject;
    }

    /**
     * Номи фан (shortcut)
     */
    public function getSubjectNameAttribute(): string
    {
        return $this->subjectAssignment?->subject?->name ?? '';
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
     */
    public function calculateTotalScore(): ?float
    {
        $examScore = max((float) ($this->exam_score ?? 0), (float) ($this->retake_score ?? 0));
        $ratingScore = $this->rating1_score;
        $journalScore = $this->rating2_score;

        if (is_null($ratingScore) || is_null($journalScore)) {
            return null;
        }

        $total = round((($ratingScore + $journalScore) / 4) + ($examScore * 0.5), 2);

        return $total;
    }

    /**
     * Ҳисоб ва сабти баҳои ниҳоӣ
     */
    public function calculateAndSetFinalGrade(): void
    {
        $examScore = max((float) ($this->exam_score ?? 0), (float) ($this->retake_score ?? 0));

        if ($examScore <= 0) {
            return;
        }

        $rating1 = (float) ($this->rating1_score ?? 0);
        $rating2 = (float) ($this->rating2_score ?? 0);

        $totalScore = round((($rating1 + $rating2) / 4) + ($examScore * 0.5), 2);

        $grade = GradeScale::fromPercentage($totalScore);

        $this->total_score = $totalScore;
        $this->letter_grade = $grade->value;
        $this->grade_point = $grade->gradePoint();
        $this->traditional_grade = $grade->traditionalGrade();

        if ($grade->isPassing()) {
            $this->status = 'passed';
            $this->credits_earned = $this->subjectAssignment?->subject?->credits ?? 0;
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
