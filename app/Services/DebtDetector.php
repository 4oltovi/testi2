<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Enums\GradeScale;
use App\Models\AcademicDebt;
use App\Models\AcademicDebtHistory;
use App\Models\SemesterGrade;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Хидмати ошкоркунии қарздории академӣ
 *
 * Қоидаҳо:
 * - Агар баҳои ниҳоӣ < 50% (Fx ё F) — қарздор аст
 * - Fx (45-49%) — имкони такрорсупорӣ дорад (ҳадди аксар 2 бор)
 * - F (0-44%) — бояд фанро дубора хонад
 * - Агар давомот < 75% — ба имтиҳон иҷозат дода намешавад
 */
class DebtDetector
{
    /**
     * Санҷиши баҳои ниҳоӣ ва эҷоди қарздорӣ (агар лозим бошад)
     */
    public function checkAndCreateDebt(SemesterGrade $semesterGrade): ?AcademicDebt
    {
        // Агар баҳо ҳоло ҳисоб нашуда бошад
        if (!$semesterGrade->letter_grade) {
            return null;
        }

        $grade = GradeScale::tryFrom($semesterGrade->letter_grade);
        if (!$grade || $grade->isPassing()) {
            // Агар қарзи пеш дошт ва ҳоло гузашт — пӯшонем
            $this->resolveExistingDebt($semesterGrade);
            return null;
        }

        // Қарздорӣ пайдо шуд
        return $this->createDebt($semesterGrade, $grade);
    }

    /**
     * Эҷоди қарздории нав
     */
    private function createDebt(SemesterGrade $semesterGrade, GradeScale $grade): AcademicDebt
    {
        return DB::transaction(function () use ($semesterGrade, $grade) {
            // Оё аллакай қарздорӣ барои ин фан/семестр мавҷуд аст?
            $existingDebt = AcademicDebt::where('student_id', $semesterGrade->student_id)
                ->where('curriculum_id', $semesterGrade->curriculum_id)
                ->where('semester_id', $semesterGrade->semester_id)
                ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->first();

            if ($existingDebt) {
                return $existingDebt;
            }

            $reason = $this->determineReason($semesterGrade);

            $debt = AcademicDebt::create([
                'student_id' => $semesterGrade->student_id,
                'semester_grade_id' => $semesterGrade->id,
                'curriculum_id' => $semesterGrade->curriculum_id,
                'subject_id' => $semesterGrade->curriculum?->subject_id,
                'semester_id' => $semesterGrade->semester_id,
                'reason' => $reason,
                'debt_date' => now(),
                'original_score' => $semesterGrade->total_score,
                'original_grade' => $grade->value,
                'retake_allowed' => $grade->canRetake(), // Fx = true, F = false
                'max_retake_attempts' => $grade->canRetake() ? 2 : 0,
                'retake_deadline' => $grade->canRetake()
                    ? $semesterGrade->semester?->retake_end_date
                    : null,
                'status' => DebtStatus::ACTIVE,
                'created_by' => Auth::id() ?? 1,
            ]);

            // Сабти таърих
            AcademicDebtHistory::create([
                'academic_debt_id' => $debt->id,
                'action' => 'created',
                'from_status' => null,
                'to_status' => DebtStatus::ACTIVE->value,
                'comment' => "Қарздории академӣ эҷод шуд. Баҳо: {$grade->value} ({$semesterGrade->total_score}%)",
                'performed_by' => Auth::id() ?? 1,
            ]);

            // Навсозии ҳолати донишҷӯ
            $semesterGrade->student->update(['has_debts' => true]);

            return $debt;
        });
    }

    /**
     * Муайян кардани сабаби қарздорӣ
     */
    private function determineReason(SemesterGrade $semesterGrade): string
    {
        if (is_null($semesterGrade->exam_score) && is_null($semesterGrade->retake_score)) {
            return 'exam_absent'; // Ба имтиҳон наомад
        }

        $examScore = $semesterGrade->retake2_score
            ?? $semesterGrade->retake_score
            ?? $semesterGrade->exam_score;

        if ($examScore !== null && $examScore < 50) {
            return 'exam_failed'; // Имтиҳонро нагузашт
        }

        if (($semesterGrade->rating1_score ?? 0) < 50 || ($semesterGrade->rating2_score ?? 0) < 50) {
            return 'rating_failed'; // Рейтинг кам
        }

        return 'exam_failed';
    }

    /**
     * Ҳал кардани қарздории мавҷуда (баъди гузаштани такрорсупорӣ)
     */
    public function resolveExistingDebt(SemesterGrade $semesterGrade): void
    {
        $debt = AcademicDebt::where('student_id', $semesterGrade->student_id)
            ->where('curriculum_id', $semesterGrade->curriculum_id)
            ->where('semester_id', $semesterGrade->semester_id)
            ->whereIn('status', [
                DebtStatus::ACTIVE,
                DebtStatus::RETAKE_SCHEDULED,
                DebtStatus::ESCALATED,
            ])
            ->first();

        if (!$debt) return;

        $debt->resolve(
            $semesterGrade->total_score,
            $semesterGrade->letter_grade,
            Auth::id() ?? 1,
            'Такрорсупорӣ бомуваффақият гузашт'
        );

        // Сабти таърих
        AcademicDebtHistory::create([
            'academic_debt_id' => $debt->id,
            'action' => 'resolved',
            'from_status' => $debt->getOriginal('status')?->value ?? 'active',
            'to_status' => DebtStatus::RESOLVED->value,
            'comment' => "Қарздорӣ ҳал шуд. Баҳои нав: {$semesterGrade->letter_grade} ({$semesterGrade->total_score}%)",
            'performed_by' => Auth::id() ?? 1,
        ]);
    }

    /**
     * Таъини такрорсупорӣ
     */
    public function scheduleRetake(AcademicDebt $debt, ?\DateTimeInterface $retakeDate = null): bool
    {
        if (!$debt->canRetake()) {
            return false;
        }

        $debt->update(['status' => DebtStatus::RETAKE_SCHEDULED]);

        AcademicDebtHistory::create([
            'academic_debt_id' => $debt->id,
            'action' => 'retake_scheduled',
            'from_status' => DebtStatus::ACTIVE->value,
            'to_status' => DebtStatus::RETAKE_SCHEDULED->value,
            'comment' => 'Такрорсупорӣ таъин шуд',
            'metadata' => json_encode(['retake_date' => $retakeDate?->format('Y-m-d')]),
            'performed_by' => Auth::id() ?? 1,
        ]);

        return true;
    }

    /**
     * Эскалатсия ба комиссия (баъди 2 бори нокомӣ)
     */
    public function escalateToCommission(AcademicDebt $debt): void
    {
        $debt->update(['status' => DebtStatus::ESCALATED]);

        AcademicDebtHistory::create([
            'academic_debt_id' => $debt->id,
            'action' => 'escalated',
            'from_status' => $debt->getOriginal('status')?->value ?? 'active',
            'to_status' => DebtStatus::ESCALATED->value,
            'comment' => 'Ба комиссияи такрорсупорӣ фиристода шуд',
            'performed_by' => Auth::id() ?? 1,
        ]);
    }

    /**
     * Гирифтани ҳамаи қарздорони як семестр
     */
    public function getDebtorsBySemester(int $semesterId): \Illuminate\Support\Collection
    {
        return AcademicDebt::where('semester_id', $semesterId)
            ->open()
            ->with(['student.user', 'subject', 'curriculum'])
            ->get()
            ->groupBy('student_id');
    }

    /**
     * Рӯйхати қарздорони як гурӯҳ
     */
    public function getDebtorsByGroup(int $groupId): \Illuminate\Support\Collection
    {
        $studentIds = Student::where('group_id', $groupId)->pluck('id');

        return AcademicDebt::whereIn('student_id', $studentIds)
            ->open()
            ->with(['student.user', 'subject'])
            ->get()
            ->groupBy('student_id');
    }

    /**
     * Санҷиши давомот барои иҷозат ба имтиҳон
     */
    public function checkAttendanceForExamAdmission(int $studentId, int $subjectAssignmentId): array
    {
        $total = \App\Models\Attendance::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->count();

        if ($total === 0) {
            return ['admitted' => true, 'percentage' => 100, 'message' => 'Маълумот мавҷуд нест'];
        }

        $present = \App\Models\Attendance::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->whereIn('status', ['present', 'late', 'excused', 'sick'])
            ->count();

        $percentage = round(($present / $total) * 100, 1);
        $admitted = $percentage >= 75; // 75% — ҳадди ақали давомот

        return [
            'admitted' => $admitted,
            'percentage' => $percentage,
            'total_lessons' => $total,
            'attended' => $present,
            'message' => $admitted
                ? "Давомот: {$percentage}% — Ба имтиҳон иҷозат дорад"
                : "Давомот: {$percentage}% — Ба имтиҳон иҷозат ДОДА НАМЕШАВАД (ҳадди ақал 75%)",
        ];
    }
}
