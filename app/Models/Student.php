<?php

namespace App\Models;

use App\Enums\GradeScale;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
        'contract_amount',
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
        'orphan_type',
        'guardian_name',
        'guardian_phone',
        'guardian_relation',
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
            'orphan_type' => \App\Enums\OrphanType::class,
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

/**
 * Ҳаҷми кредитҳои гирифташуда ( танҳо фанҳои гузашта ).
 *
 * Солиби — сутуни `students.total_credits_earned`, ки ҳеҷ гоҳ аз коди барнома
 * пур намешавад ( танҳо сеeder) ва аз ин сабаб ҳама вақт 0 мемонанд.
 * Ин метод маълумотро ҳар дафра аз `semester_grades` ҳисоб мекунад.
 *
 * Қоидаҳо:
 *  - танҳо баҳои гузашта (`status = passed`) ҳисоб мешаванд; F ё ҳолатҳои
 *    дигар (retake, failed, ӳуқуқӣ) кредит надоранд;
 *  - як фан танҳо як маротиба ҳисоб мешавад, ҳатто агар чанд сатр
 *    баҳо/такрорӣ дошта бошад — маълумоти он аз рӯи `subject_id` ҷамъ мешавад;
 *  - кредит аз `SubjectAssignment::credits` гирифта мешавад (ҳамон манбаъе,
 *    ки ведомост истифода мебарад).
 */
public function earnedCredits(): int
{
    $passed = $this->semesterGrades()
        ->where('status', 'passed')
        ->whereNotNull('subject_assignment_id')
        ->whereNotNull('subject_id')
        ->with('subjectAssignment')
        ->get(['subject_id', 'subject_assignment_id']);

    // Як фан = як маротиба
    return (int) $passed
        ->groupBy('subject_id')
        ->map(function ($rows) {
            $credits = 0;

            foreach ($rows as $row) {
                $value = (int) ($row->subjectAssignment?->credits ?? 0);

                if ($value > $credits) {
                    $credits = $value;
                }
            }

            return $credits;
        })
        ->sum();
}

/**
 * Атрибути кутоӣ барои қутоҳҳо ва ҳуҷҷатҳо.
 */
public function getEarnedCreditsAttribute(): int
{
    return $this->earnedCredits();
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

    public function contractPayments(): HasMany
    {
        return $this->hasMany(\App\Models\ContractPayment::class);
    }

    public function getContractPaidAttribute(): float
    {
        return (float) $this->contractPayments()->sum('amount');
    }

    public function getContractRemainingAttribute(): float
    {
        return max(0, (float) ($this->contract_amount ?? 0) - $this->contract_paid);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::ACTIVE);
    }

    /**
 * Донишҷӯёне, ки ҳоло қарздории кушода доранд.
 *
     * Пештар ин сутури муҳқӯмии `students.has_debts` истифода мешуд, ки танҳо
     * нишондиҳи он аст, ки дар як вақт қарздорӣ ВОҲИД буд — на ин ки ҳоло
     * ҳаст. Агар қарз ҳал шавад, ин сутур метавонад дар ҳолатӣ сӯпоранда
     * бимонад, агар ба куваи дигар тағйир дода шавад — ё баръакс.
     * Акнун аз ҳамон манбаъ истифода мекунем, ки ҳисоботи қарздорон
     * (`AcademicDebt::scopeOpen`) истифода мекунад, то ҳамаҳо як хел
     * ҳисоб кунанд.
     */
    public function scopeWithDebts($query)
    {
        return $query->whereHas('academicDebts', fn ($q) => $q->open());
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
     *
     * Агар ҳеч сабти давомот набошад, `null` баргардоранда мешавад (на 100),
     * то ки панел «—» нишон диҳад, чунки 100% бе далел хато аст.
     */
    public function getAttendancePercentage(int $semesterId = null): ?float
    {
        $query = DB::table('daily_attendance')
            ->where('student_id', $this->id)
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id');

        $total = $query->count();
        if ($total === 0) {
            return null;
        }

        $present = (clone $query)->where('daily_attendance.status', 'present')->count();
        return round(($present / $total) * 100, 1);
    }
}
