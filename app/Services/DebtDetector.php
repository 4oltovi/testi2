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
     * Санҷиш ва эҷоди қарздорӣ барои баҳои ниҳоӣ
     */
    public function checkAndCreateDebt(SemesterGrade $semesterGrade): ?AcademicDebt
    {
        // Агар баҳо тасдиқ нашуда бошад, қарздорӣ эҷод намешавад
        if (!$semesterGrade->is_finalized) {
            return null;
        }

        // Агар баҳо гузашта бошад, қарздорӣ нест
        if ($semesterGrade->isPassed()) {
            return null;
        }

        // Гирифтани GradeScale enum
        $grade = GradeScale::tryFrom($semesterGrade->letter_grade);

        if (!$grade) {
            return null;
        }

        // Эҷоди қарздорӣ
        return $this->createDebt($semesterGrade, $grade);
    }

    /**
     * Эҷоди қарздории нав
     */
    private function createDebt(SemesterGrade $semesterGrade, GradeScale $grade): AcademicDebt
    {
        return DB::transaction(function () use ($semesterGrade, $grade) {
            $subjectId = $semesterGrade->subjectAssignment?->subject_id;

            if (!$subjectId) {
                throw new \RuntimeException('Имкони эҷоди қарздорӣ нест: subject_id ё subject_assignment_id муайян нашудааст.');
            }

            $existingDebt = AcademicDebt::where('student_id', $semesterGrade->student_id)
                ->where('subject_id', $subjectId)
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
                'subject_id' => $subjectId,
                'semester_id' => $semesterGrade->semester_id,
                'reason' => $reason,
                'debt_date' => now(),
                'original_score' => $semesterGrade->total_score,
                'original_grade' => $grade->value,
                'retake_allowed' => $grade->canRetake(),
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
        $grade = GradeScale::tryFrom($semesterGrade->letter_grade);

        if ($grade && !$grade->isPassing()) {
            return 'exam_failed';
        }

        if ($this->isAttendanceLow($semesterGrade)) {
            return 'low_attendance';
        }

        return 'exam_failed';
    }

    /**
     * Санҷиши давомоти донишҷӯ барои фан
     */
    private function isAttendanceLow(SemesterGrade $semesterGrade): bool
    {
        $minPercentage = config('donishor.grading.min_attendance_percentage', 75);

        $groupId = $semesterGrade->subjectAssignment?->group_id;
        if (!$groupId) {
            return false;
        }

        $attendance = DB::table('daily_attendance')
            ->where('student_id', $semesterGrade->student_id)
            ->where('group_id', $groupId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present
            ')
            ->first();

        if (!$attendance || $attendance->total == 0) {
            return false;
        }

        $percentage = ($attendance->present / $attendance->total) * 100;

        return $percentage < $minPercentage;
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
     * Рӯйхати қарздорони як донишҷӯ
     */
    public function getDebtsByStudent(int $studentId): \Illuminate\Support\Collection
    {
        return AcademicDebt::where('student_id', $studentId)
            ->open()
            ->with(['subject', 'semester'])
            ->orderByDesc('debt_date')
            ->get();
    }

    /**
     * Синхронизатсияи қарздориҳо барои фан/семестр
     *
     * Ҳамаи донишҷӯёни гурӯҳро тафтиш мекунад:
     * - Агар донишҷӯ имтиҳонро насупорид ё наомад → exam_score = 0
     * - Агар final_score < 50 → қарздор эҷод/алоқаманд мекунад
     * - Агар final_score >= 50 → қарздорӣ ҳал/нест мекунад
     */
    public function syncDebtsForSubject(int $subjectId, int $semesterId, ?int $excludeStudentId = null): void
    {
        $subjectAssignments = \App\Models\SubjectAssignment::where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->where('is_active', true)
            ->get();

        if ($subjectAssignments->isEmpty()) {
            return;
        }

        $groupIds = $subjectAssignments->pluck('group_id')->unique()->filter();
        if ($groupIds->isEmpty()) {
            return;
        }

        $students = Student::whereIn('group_id', $groupIds)
            ->when($excludeStudentId, fn($q) => $q->where('id', '!=', $excludeStudentId))
            ->get();

        $gradeCalculator = app(\App\Services\GradeCalculator::class);

        foreach ($students as $student) {
            $subjectAssignment = $subjectAssignments->firstWhere('group_id', $student->group_id);
            if (!$subjectAssignment) {
                continue;
            }

            $rating1 = $gradeCalculator->calculateRating1($student->id, $subjectAssignment->id, $semesterId);
            $rating2 = $gradeCalculator->calculateRating2($student->id, $subjectAssignment->id, $semesterId);
            $exam = $gradeCalculator->calculateExamScore($student->id, $subjectAssignment->id, $semesterId) ?? 0;

            $retakeScore = null;
            $retakeExam = \App\Models\RetakeExam::where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->first();

            if ($retakeExam) {
                $retakeStudent = \App\Models\RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                    ->where('student_id', $student->id)
                    ->first();

                if ($retakeStudent && $retakeStudent->score !== null) {
                    $retakeScore = (float) $retakeStudent->score;
                }
            }

            $effectiveExamScore = $retakeScore !== null ? $retakeScore : $exam;
            $finalScore = round((($rating1 + $rating2) / 4) + ($effectiveExamScore * 0.5), 2);

            $existingDebt = AcademicDebt::where('student_id', $student->id)
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->first();

            if ($finalScore < 50) {
                $grade = GradeScale::fromPercentage($finalScore);

                $semesterGrade = SemesterGrade::where('student_id', $student->id)
                    ->where('subject_assignment_id', $subjectAssignment->id)
                    ->where('semester_id', $semesterId)
                    ->first();

                if ($existingDebt) {
                    $existingDebt->update([
                        'original_score' => $finalScore,
                        'original_grade' => $grade->value,
                        'retake_allowed' => $grade->canRetake(),
                        'max_retake_attempts' => $grade->canRetake() ? 2 : 0,
                        'semester_grade_id' => $semesterGrade?->id,
                    ]);
                } else {
                    AcademicDebt::create([
                        'student_id' => $student->id,
                        'semester_grade_id' => $semesterGrade?->id,
                        'subject_id' => $subjectId,
                        'semester_id' => $semesterId,
                        'reason' => 'exam_failed',
                        'debt_date' => now(),
                        'original_score' => $finalScore,
                        'original_grade' => $grade->value,
                        'retake_allowed' => $grade->canRetake(),
                        'max_retake_attempts' => $grade->canRetake() ? 2 : 0,
                        'status' => DebtStatus::ACTIVE,
                        'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                    ]);

                    $student->update(['has_debts' => true]);
                }
            } else {
                if ($existingDebt) {
                    $existingDebt->resolve($finalScore, GradeScale::fromPercentage($finalScore)->value, \Illuminate\Support\Facades\Auth::id() ?? 1);
                }
            }
        }
    }

    /**
     * Автоматик: донишҷӯёни ба имтиҳон наомада ё насупоридаро F гузошта, ба қарздорон илова кардан
     */
    public function autoFailAbsentStudents(int $subjectId, int $semesterId, ?int $excludeStudentId = null): void
    {
        $subjectAssignments = \App\Models\SubjectAssignment::where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->where('is_active', true)
            ->get();

        if ($subjectAssignments->isEmpty()) {
            return;
        }

        $groupIds = $subjectAssignments->pluck('group_id')->unique()->filter();
        if ($groupIds->isEmpty()) {
            return;
        }

        $students = Student::whereIn('group_id', $groupIds)
            ->when($excludeStudentId, fn($q) => $q->where('id', '!=', $excludeStudentId))
            ->get();

        foreach ($students as $student) {
            $subjectAssignment = $subjectAssignments->firstWhere('group_id', $student->group_id);
            if (!$subjectAssignment) {
                continue;
            }

            $exam = \App\Models\Exam::where('subject_assignment_id', $subjectAssignment->id)
                ->where('semester_id', $semesterId)
                ->where('exam_type', 'main')
                ->latest('starts_at')
                ->first();

            if (!$exam) {
                continue;
            }

            $attempt = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'auto_submitted', 'graded'])
                ->orderByDesc('submitted_at')
                ->first();

            if ($attempt) {
                continue;
            }

            $rating1 = app(\App\Services\GradeCalculator::class)->calculateRating1($student->id, $subjectAssignment->id, $semesterId);
            $rating2 = app(\App\Services\GradeCalculator::class)->calculateRating2($student->id, $subjectAssignment->id, $semesterId);
            $finalScore = round((($rating1 + $rating2) / 4), 2);

            $existingDebt = AcademicDebt::where('student_id', $student->id)
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->first();

            if ($finalScore < 50 || $existingDebt) {
                $grade = GradeScale::fromPercentage($finalScore);

                $semesterGrade = SemesterGrade::where('student_id', $student->id)
                    ->where('subject_assignment_id', $subjectAssignment->id)
                    ->where('semester_id', $semesterId)
                    ->first();

                if ($existingDebt) {
                    $existingDebt->update([
                        'original_score' => $finalScore,
                        'original_grade' => $grade->value,
                        'retake_allowed' => $grade->canRetake(),
                        'max_retake_attempts' => $grade->canRetake() ? 2 : 0,
                        'semester_grade_id' => $semesterGrade?->id,
                    ]);
                } else {
                    AcademicDebt::create([
                        'student_id' => $student->id,
                        'semester_grade_id' => $semesterGrade?->id,
                        'subject_id' => $subjectId,
                        'semester_id' => $semesterId,
                        'reason' => 'exam_absent',
                        'debt_date' => now(),
                        'original_score' => $finalScore,
                        'original_grade' => $grade->value,
                        'retake_allowed' => $grade->canRetake(),
                        'max_retake_attempts' => $grade->canRetake() ? 2 : 0,
                        'status' => DebtStatus::ACTIVE,
                        'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                    ]);

                    $student->update(['has_debts' => true]);
                }
            }
        }
    }

    /**
     * Ҳал кардани қарздорӣ баъди гузаштани такрорсупорӣ
     */
    public function resolveDebtAfterRetake(int $studentId, int $subjectId, int $semesterId, float $newScore, string $newGrade): void
    {
        $existingDebt = AcademicDebt::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
            ->first();

        if (!$existingDebt) {
            return;
        }

        $existingDebt->resolve($newScore, $newGrade, \Illuminate\Support\Facades\Auth::id() ?? 1);
    }
}
