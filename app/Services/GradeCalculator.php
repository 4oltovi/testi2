<?php

namespace App\Services;

use App\Enums\GradeScale;
use App\Models\CategoryScore;
use App\Models\CurrentGrade;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\RetakeExam;
use App\Models\RetakeExamStudent;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SubjectAssignment;
use Illuminate\Support\Facades\Cache;

/**
 * Хидмати ҳисоби баҳоҳо
 *
 * ЛОГИКАИ НАВ:
 * Рейтинг (R1/R2) = Журнали электронӣ (категорияҳо) → то X бал (танзимот: 60)
 *                 + Тести компютерӣ (rating1/rating2) → то (100 − X) бал (40)
 */
class GradeCalculator
{
    private const CACHE_TTL = 300; // 5 дақиқа

    // ================================================================
    // НАВ: Рейтинги пурра R1 = журнал (60) + тест (40)
    // ================================================================
    public function calculateRating1(int $studentId, int $subjectAssignmentId, int $semesterId): float
    {
        return $this->calculateCombinedRating($studentId, $subjectAssignmentId, $semesterId, 'rating1');
    }

    public function calculateRating2(int $studentId, int $subjectAssignmentId, int $semesterId): float
    {
        return $this->calculateCombinedRating($studentId, $subjectAssignmentId, $semesterId, 'rating2');
    }

    private function calculateCombinedRating(int $studentId, int $subjectAssignmentId, int $semesterId, string $period): float
    {
        $journalMax = (float) Setting::get('journal_part_points', 60);
        $testMax = max(0, 100 - $journalMax);

        $journalScore = $this->calculateJournalPercentage($studentId, $subjectAssignmentId, $semesterId, $period);

        $testPct = $this->calculateTestPercentage($studentId, $subjectAssignmentId, $semesterId, $period);

        return round(min($journalScore, $journalMax) + ($testPct / 100 * $testMax), 2);
    }

    // ================================================================
    // НАВ: Фоизи ЖУРНАЛИ ЭЛЕКТРОНӢ (аз категорияҳои омӯзгор)
    // ================================================================
    public function calculateJournalPercentage(int $studentId, int $subjectAssignmentId, int $semesterId, string $period = 'rating1'): float
    {
        $semester = Semester::find($semesterId);

        if (!$semester || !$semester->start_date) {
            return 0;
        }

        $scores = CategoryScore::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->where('period', $period)
            ->get();

        if ($scores->isNotEmpty()) {
            return round($scores->sum('score'), 2);
        }

        $grades = CurrentGrade::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->whereBetween('week_number', [1, $period === 'rating1' ? 8 : 16])
            ->get();

        return $this->calculateAverageScore($grades);
    }

    // ================================================================
    // НАВ: Фоизи ТЕСТ (аз имтиҳони rating1/rating2, ки донишҷӯ бо компютер супурд)
    // ================================================================
    public function calculateTestPercentage(int $studentId, int $subjectAssignmentId, int $semesterId, string $period = 'rating1'): float
    {
        // НАВ: аввал аз рейтингҳои онлайн (rating_attempts)
        $subjectId = \App\Models\SubjectAssignment::whereKey($subjectAssignmentId)->value('subject_id');

        if ($subjectId) {
            $attempt = \App\Models\RatingAttempt::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('status', 'finished')
                ->whereHas('session', fn($q) => $q
                    ->where('period', $period)
                    ->where('semester_id', $semesterId))
                ->orderByDesc('percentage')
                ->first();

            if ($attempt) {
                return (float) $attempt->percentage;
            }
        }

        // Захира: имтиҳонҳои кӯҳна (exam_type = rating1/rating2)
        $exam = Exam::where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->where('exam_type', $period)
            ->latest()
            ->first();

        if (!$exam) return 0;

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->orderByDesc('total_score')
            ->first();

        if (!$attempt) return 0;

        $maxPoints = $exam->examQuestions()->sum('points');
        if ($maxPoints <= 0) $maxPoints = (float) $exam->total_questions_count * 2.5;

        return $maxPoints > 0 ? round(min(100, ($attempt->total_score / $maxPoints) * 100), 2) : 0;
    }

    // ================================================================
    // Коэффисиентҳо (барои формулаи ниҳоӣ)
    // ================================================================
    private function getWeights(bool $withIndependentWork = false): array
    {
        if ($withIndependentWork) {
            return [
                'rating1' => (float) Setting::get('formula_weight_rating1_with_iw', 0.15),
                'rating2' => (float) Setting::get('formula_weight_rating2_with_iw', 0.15),
                'independent_work' => (float) Setting::get('formula_weight_independent_work', 0.30),
                'exam' => (float) Setting::get('formula_weight_exam_with_iw', 0.40),
            ];
        }

        return [
            'rating1' => (float) Setting::get('formula_weight_rating1', 0.30),
            'rating2' => (float) Setting::get('formula_weight_rating2', 0.30),
            'exam' => (float) Setting::get('formula_weight_exam', 0.40),
        ];
    }

    /**
     * Миёнаи баллҳо (аз 100) — системаи кӯҳна
     */
    private function calculateAverageScore($grades): float
    {
        if ($grades->isEmpty()) return 0;

        $totalNormalized = $grades->sum(function ($grade) {
            return $grade->max_score > 0
                ? ($grade->score / $grade->max_score) * 100
                : 0;
        });

        return round($totalNormalized / $grades->count(), 2);
    }

    /**
     * Категорияҳо (бе тақсим ба давра) — барои гузоришҳо
     */
    public function calculateRatingFromCategories(int $studentId, int $subjectAssignmentId, int $semesterId, string $period = 'rating1'): float
    {
        $scores = CategoryScore::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->where('period', $period)
            ->get();

        if ($scores->isEmpty()) return 0;

        return round($scores->sum('score'), 2);
    }

    // ================================================================
    // Баҳои ниҳоӣ
    // ================================================================
    /**
     * ФОРМУЛАИ НАВ:
     * Ниҳоӣ = (R1 + R2) ÷ 4 + Имтиҳон × 0,5
     */
    public function calculateFinalGrade(SemesterGrade $semesterGrade): array
    {
        $examScore = max(
            (float) ($semesterGrade->exam_score ?? 0),
            (float) ($semesterGrade->retake_score ?? 0)
        );

        $rating1 = (float) ($semesterGrade->rating1_score ?? 0);
        $rating2 = (float) ($semesterGrade->rating2_score ?? 0);

        if ($examScore <= 0 && $rating1 <= 0 && $rating2 <= 0) {
            return [
                'total_score' => null,
                'letter_grade' => null,
                'grade_point' => null,
                'traditional_grade' => null,
                'is_passing' => null,
                'can_retake' => null,
                'must_repeat' => null,
                'weights_used' => null,
            ];
        }

        $totalScore = round(($rating1 + $rating2) / 4 + $examScore, 2);

        $grade = GradeScale::fromPercentage($totalScore);

        return [
            'total_score' => $totalScore,
            'letter_grade' => $grade->value,
            'grade_point' => $grade->gradePoint(),
            'traditional_grade' => $grade->traditionalGrade(),
            'is_passing' => $grade->isPassing(),
            'can_retake' => $grade->canRetake(),
            'must_repeat' => $grade->mustRepeatCourse(),
            'weights_used' => [
                'rating1' => $rating1,
                'rating2' => $rating2,
                'exam' => $examScore,
            ],
        ];
    }

    /**
     * Автоматикӣ ҳисоб ва сабти баҳои ниҳоӣ
     * НАВ: агар R1/R2 дастӣ сабт нашуда бошанд — аз журнал+тест гирифта мешаванд
     */
    public function processAndSaveFinalGrade(SemesterGrade $semesterGrade): SemesterGrade
    {
        // 1) R1 автоматӣ (журнал + тести рейтинг 1)
        if ($semesterGrade->rating1_score === null && $semesterGrade->subject_assignment_id) {
            $semesterGrade->rating1_score = $this->calculateRating1(
                $semesterGrade->student_id,
                $semesterGrade->subject_assignment_id,
                $semesterGrade->semester_id
            );
        }

        // 2) R2 автоматӣ (журнал + тести рейтинг 2)
        if ($semesterGrade->rating2_score === null && $semesterGrade->subject_assignment_id) {
            $semesterGrade->rating2_score = $this->calculateRating2(
                $semesterGrade->student_id,
                $semesterGrade->subject_assignment_id,
                $semesterGrade->semester_id
            );
        }

        // 3) ИМТИҲОН автоматӣ аз тести онлайн (омӯзгор дастӣ намегузорад!)
        if ($semesterGrade->exam_score === null && $semesterGrade->subject_assignment_id) {
            $semesterGrade->exam_score = $this->calculateExamPercentage(
                $semesterGrade->student_id,
                $semesterGrade->subject_assignment_id,
                $semesterGrade->semester_id
            );
        }

        // 4) Натиҷаи такрорсупорӣ
        if ($semesterGrade->subject_assignment_id) {
            $retakeExam = RetakeExam::where('subject_id', $semesterGrade->subjectAssignment->subject_id)
                ->where('semester_id', $semesterGrade->semester_id)
                ->first();

            if ($retakeExam) {
                $retakeExamStudent = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                    ->where('student_id', $semesterGrade->student_id)
                    ->first();

                if ($retakeExamStudent && $retakeExamStudent->score !== null) {
                    $semesterGrade->retake_score = (float) $retakeExamStudent->score;
                }
            }
        }

        $result = $this->calculateFinalGrade($semesterGrade);

        if ($result['total_score'] === null) {
            return $semesterGrade;
        }

        $semesterGrade->total_score = $result['total_score'];
        $semesterGrade->letter_grade = $result['letter_grade'];
        $semesterGrade->grade_point = $result['grade_point'];
        $semesterGrade->traditional_grade = $result['traditional_grade'];

        if ($result['is_passing']) {
            $semesterGrade->status = 'passed';
            $semesterGrade->credits_earned = $semesterGrade->subjectAssignment?->credits ?? 0;
        } elseif ($result['can_retake']) {
            $semesterGrade->status = 'retake';
            $semesterGrade->credits_earned = 0;
        } else {
            $semesterGrade->status = 'failed';
            $semesterGrade->credits_earned = 0;
        }

        $semesterGrade->save();

        if (!$semesterGrade->is_finalized) {
            $semesterGrade->update([
                'is_finalized' => true,
                'finalized_at' => now(),
                'finalized_by' => auth()->id(),
            ]);
            $semesterGrade->refresh();
        }

        if (!$result['is_passing']) {
            app(\App\Services\DebtDetector::class)->checkAndCreateDebt($semesterGrade);
        }

        return $semesterGrade;
    }

    /**
     * Хулосаи донишҷӯ дар як фан
     */
    public function getStudentSubjectSummary(int $studentId, int $subjectAssignmentId, int $semesterId): array
    {
        $semesterGrade = SemesterGrade::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->first();

        $allCurrentGrades = CurrentGrade::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->orderBy('week_number')
            ->get();

        $categoryScores = CategoryScore::where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->get();

        return [
            'rating1' => $this->calculateRating1($studentId, $subjectAssignmentId, $semesterId),
            'rating2' => $this->calculateRating2($studentId, $subjectAssignmentId, $semesterId),
            'current_grades' => $allCurrentGrades,
            'category_scores' => $categoryScores,
            'semester_grade' => $semesterGrade,
            'total_grades_count' => $allCurrentGrades->count(),
            'total_category_scores' => $categoryScores->count(),
        ];
    }

    /**
     * Рейтинги умумии донишҷӯ дар семестр
     */
    public function getStudentSemesterRating(int $studentId, int $semesterId): array
    {
        $grades = SemesterGrade::where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->where('is_finalized', true)
            ->with('subjectAssignment.subject')
            ->get();

        $totalScore = $grades->avg('total_score') ?? 0;
        $passedCount = $grades->where('status', 'passed')->count();
        $failedCount = $grades->whereIn('status', ['failed', 'retake', 'debt'])->count();

        return [
            'average_score' => round($totalScore, 2),
            'total_subjects' => $grades->count(),
            'passed' => $passedCount,
            'failed' => $failedCount,
            'grades' => $grades,
        ];
    }
    /**
     * ПУРРА: ҳисоб ва сабти баҳоҳои семестр дар semester_grades
     */
    public function recalculateAndPersist(int $studentId, int $subjectAssignmentId, int $semesterId): void
    {
        $rating1 = $this->calculateRating1($studentId, $subjectAssignmentId, $semesterId);
        $rating2 = $this->calculateRating2($studentId, $subjectAssignmentId, $semesterId);
        $exam = $this->calculateExamPercentage($studentId, $subjectAssignmentId, $semesterId, 'main');

        $retakeScore = null;
        $retakeExam = RetakeExam::where('subject_id', SubjectAssignment::find($subjectAssignmentId)?->subject_id)
            ->where('semester_id', $semesterId)
            ->first();

        if ($retakeExam) {
            $retakeStudent = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                ->where('student_id', $studentId)
                ->first();

            if ($retakeStudent && $retakeStudent->score !== null) {
                $retakeScore = (float) $retakeStudent->score;
            }
        }

        $effectiveExamScore = $retakeScore ?? $exam;

        $totalScore = null;
        $letterGrade = null;
        $gradePoint = null;
        $status = null;

        if ($effectiveExamScore > 0 || ($rating1 > 0 || $rating2 > 0)) {
            $totalScore = round((($rating1 + $rating2) / 4) + $effectiveExamScore, 2);

            $gradeEnum = GradeScale::fromPercentage($totalScore);
            $letterGrade = $gradeEnum->value;
            $gradePoint = $gradeEnum->gradePoint();
            $status = $gradeEnum->isPassing() ? 'passed' : ($gradeEnum->canRetake() ? 'retake' : 'failed');
        }

        SemesterGrade::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_assignment_id' => $subjectAssignmentId,
                'semester_id' => $semesterId,
            ],
            [
                'rating1_score' => $rating1,
                'rating2_score' => $rating2,
                'exam_score' => $exam,
                'retake_score' => $retakeScore,
                'total_score' => $totalScore,
                'letter_grade' => $letterGrade,
                'grade_point' => $gradePoint,
                'status' => $status,
            ]
        );
    }

    /**
     * НАВ: Имтиҳон аз ТЕСТИ ОНЛАЙН (автоматӣ, на дастӣ)
     * main → retake → retake2 (агар такрор супорида бошад, ҳамон ҳисоб мешавад)
     */
    public function calculateExamPercentage(int $studentId, int $subjectAssignmentId, int $semesterId, string $examType = 'main'): float
    {
        $query = Exam::where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId);

        if ($examType === 'main') {
            $query->where('exam_type', 'main');
        } else {
            $query->whereIn('exam_type', ['main', 'retake', 'retake_commission']);
        }

        $exam = $query->latest('starts_at')->first();

        if (!$exam) {
            return 0;
        }

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'auto_submitted', 'graded'])
            ->orderByDesc('submitted_at')
            ->first();

        if (!$attempt) {
            return 0;
        }

        if ($attempt->percentage !== null) {
            return (float) $attempt->percentage;
        }

        $maxPoints = $exam->examQuestions()->sum('points');
        if ($maxPoints <= 0) {
            $maxPoints = (float) $exam->total_questions_count * 2.5;
        }

        if ($maxPoints > 0) {
            return round(min(100, ($attempt->total_score / $maxPoints) * 100), 2);
        }

        return 0;
    }
}
