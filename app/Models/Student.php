<?php

namespace App\Models;

use App\Enums\GradeScale;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'group_id',
        'specialty_id',
        'course_id',
        'student_id_number',
        'record_book_number',
        'birth_date',
        'gender',
        'nationality',
        'citizenship',
        'passport_series',
        'passport_number',
        'inn',
        'address_permanent',
        'address_current',
        'parent_phone',
        'parent_name',
        'education_form',
        'study_form',
        'enrollment_date',
        'enrollment_order',
        'expected_graduation',
        'status',
        'status_date',
        'status_order',
        'status_reason',
        'cumulative_gpa',
        'total_credits_earned',
        'has_debts',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'birth_date' => 'date',
            'enrollment_date' => 'date',
            'expected_graduation' => 'date',
            'status_date' => 'date',
            'cumulative_gpa' => 'decimal:2',
            'has_debts' => 'boolean',
        ];
    }

    // ==================== РОБИТАҲО ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semesterGrades(): HasMany
    {
        return $this->hasMany(SemesterGrade::class);
    }

    public function academicDebts(): HasMany
    {
        return $this->hasMany(AcademicDebt::class);
    }

    public function activeDebts(): HasMany
    {
        return $this->hasMany(AcademicDebt::class)->whereIn('status', ['active', 'retake_scheduled', 'escalated']);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(StudentPromotion::class);
    }

    public function semesterGpas(): HasMany
    {
        return $this->hasMany(SemesterGpa::class);
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::ACTIVE);
    }

    public function scopeWithDebts($query)
    {
        return $query->where('has_debts', true);
    }

    public function scopeByGroup($query, int $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeBySpecialty($query, int $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    // ==================== МЕТОДҲО ====================

    /**
     * Номи пурра тавассути user
     */
    public function getFullNameAttribute(): string
    {
        return $this->user->full_name;
    }

    /**
     * Оё фаъол аст?
     */
    public function isActive(): bool
    {
        return $this->status === StudentStatus::ACTIVE;
    }

    /**
     * Шумораи қарздориҳои кушод
     */
    public function getActiveDebtsCountAttribute(): int
    {
        return $this->activeDebts()->count();
    }

    /**
     * Фоизи давомот дар ин семестр
     */
    public function getAttendancePercentage(int $semesterId = null): float
    {
        $query = $this->attendances();
        if ($semesterId) {
            $query->whereHas('subjectAssignment', fn($q) => $q->where('semester_id', $semesterId));
        }

        $total = $query->count();
        if ($total === 0) return 100.0;

        $present = (clone $query)->whereIn('status', ['present', 'late', 'excused', 'sick'])->count();
        return round(($present / $total) * 100, 1);
    }
}
