<?php

namespace App\Services;

use App\Enums\GradeScale;
use App\Models\CategoryScore;
use App\Models\CurrentGrade;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\RetakeExam;
use App\Models\RetakeExamStudent;
use App\Models\RatingAttempt;
use App\Models\RatingSession;
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
    private array $subjectIds = [];
    private array $semesterStartDates = [];
    private array $exams = [];
    private array $examMaxPoints = [];

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
        // Гирифтани танзимоти тақсимоти балл (60 ба журнал, 40 ба тест)
        $journalMax = (float) Setting::get('journal_part_points', 60);
        $testMax = max(0, 100 - $journalMax);

        // 1. Ҳисоби балл аз Журнал (то 60 балл)
        $journalPercentage = $this->calculateJournalPercentage($studentId, $subjectAssignmentId, $semesterId, $period);
        $finalJournalPart = min($journalPercentage, $journalMax);
        
        // 2. Ҳисоби балл аз Тест (то 40 балл) - используем наш новый метод
        $finalTestPart = $this->calculateComputerRatingScore($studentId, $subjectAssignmentId, $semesterId, $period);

        // Натиҷаи ниҳоӣ = Журнал + Тест
        return round($finalJournalPart + $finalTestPart, 2);
    }

    // ================================================================
    // НАВ: Фоизи ЖУРНАЛИ ЭЛЕКТРОНӢ (аз категорияҳои омӯзгор)
    // ================================================================
    public function calculateJournalPercentage(int $studentId, int $subjectAssignmentId, int $semesterId, string $period = 'rating1'): float
    {
        if (!array_key_exists($semesterId, $this->semesterStartDates)) {
            $this->semesterStartDates[$semesterId] = Semester::whereKey($semesterId)->value('start_date');
        }

        if (!$this->semesterStartDates[$semesterId]) {
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
    // НАВ: БАЛЛИ ТЕСТИ КОМПЬЮТЕРӢ (ҳадди аксар 40 ба эътибори фоиз)
    // ================================================================
    public function calculateComputerRatingScore(int $studentId, int $subjectAssignmentId, int $semesterId, string $period = 'rating1'): float
    {
        // 1) аввал аз rating_attempts (online)
        $subjectId = $this->subjectIds[$subjectAssignmentId]
            ??= SubjectAssignment::whereKey($subjectAssignmentId)->value('subject_id');

        \Log::debug('calculateComputerRatingScore START', [
            'student_id' => $studentId,
            'subject_assignment_id' => $subjectAssignmentId,
            'semester_id' => $semesterId,
            'period' => $period,
            'subject_id' => $subjectId,
        ]);

        if ($subjectId) {
            $attempt = RatingAttempt::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('status', 'finished')
                ->whereHas('session', fn($q) => $q
                    ->where('period', $period)
                    ->where('semester_id', $semesterId))
                ->orderByDesc('percentage')
                ->first();

            \Log::debug('calculateComputerRatingScore query params', [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'semester_id' => $semesterId,
                'period' => $period,
                'attempt_found' => $attempt?->id,
                'attempt_percentage' => $attempt?->percentage,
                'attempt_status' => $attempt?->status,
                'attempt_session_id' => $attempt?->rating_session_id,
            ]);

            if ($attempt) {
                $score = (float) min($attempt->percentage * 0.4, 40);
                \Log::debug('calculateComputerRatingScore RESULT', ['score' => $score]);
                return $score;
            }
        }

        \Log::debug('calculateComputerRatingScore FALLBACK to old exam attempts', [
            'subject_assignment_id' => $subjectAssignmentId,
            'semester_id' => $semesterId,
            'period' => $period,
        ]);

        // 2) омодагии кӯҳна дар exam_attempts (кӯҳна)

        // 2) омодагии кӯҳна дар exam_attempts (кӯҳна)
        $exam = $this->findExam($subjectAssignmentId, $semesterId, $period);

        if (!$exam) return 0;

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->orderByDesc('total_score')
            ->first();

        if (!$attempt) return 0;

        $maxPoints = $this->examMaxPoints[$exam->id]
            ??= (float) $exam->examQuestions()->sum('points');
        if ($maxPoints <= 0) $maxPoints = (float) $exam->total_questions_count * 2.5;

        if ($maxPoints <= 0) return 0;

        // percentage = (score/max) * 100, ба балли 40 табдил меёбад
        $percentage = ($attempt->total_score / $maxPoints) * 100;
        $score = $percentage * 0.4;

        return (float) min($score, 40);
    }

    // ================================================================
    // НАВ: Фоизи ТЕСТ (аз имтиҳони rating1/rating2, ки донишҷӯ бо компютер супурд)
    // ================================================================
    public function calculateTestPercentage(int $studentId, int $subjectAssignmentId, int $semesterId, string $period = 'rating1'): float
    {
        // НАВ: аввал аз рейтингҳои онлайн (rating_attempts)
        $subjectId = $this->subjectIds[$subjectAssignmentId]
            ??= SubjectAssignment::whereKey($subjectAssignmentId)->value('subject_id');

        if ($subjectId) {
            $attempt = RatingAttempt::where('student_id', $studentId)
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
        $exam = $this->findExam($subjectAssignmentId, $semesterId, $period);

        if (!$exam) return 0;

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->orderByDesc('total_score')
            ->first();

        if (!$attempt) return 0;

        $maxPoints = $this->examMaxPoints[$exam->id]
            ??= (float) $exam->examQuestions()->sum('points');
        if ($maxPoints <= 0) $maxPoints = (float) $exam->total_questions_count * 2.5;

        return $maxPoints > 0 ? round(min(100, ($attempt->total_score / $maxPoints) * 100), 2) : 0;
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

        $totalScore = round((($rating1 + $rating2) / 4) + ($examScore * 0.5), 2);

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
        if ($semesterGrade->subject_assignment_id) {
            $examScore = $this->calculateExamScore(
                $semesterGrade->student_id,
                $semesterGrade->subject_assignment_id,
                $semesterGrade->semester_id
            );
            if ($examScore !== null) {
                $semesterGrade->exam_score = $examScore;
            }
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
        
        $exam = $this->calculateExamScore($studentId, $subjectAssignmentId, $semesterId);
        
        $assignment = SubjectAssignment::find($subjectAssignmentId);
        $subjectId = $assignment?->subject_id;
        
        if (!$subjectId) {
            return;
        }

        $retakeScore = null;
        $retakeExam = RetakeExam::where('subject_id', $subjectId)
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

        $effectiveExamScore = $retakeScore !== null ? $retakeScore : ($exam ?? 0);

        $totalScore = null;
        $letterGrade = null;
        $gradePoint = null;
        $status = 'in_progress';

        if ($effectiveExamScore > 0 || ($rating1 > 0 || $rating2 > 0)) {
            $totalScore = round((((float)$rating1 + (float)$rating2) / 4) + ((float)$effectiveExamScore * 0.5), 2);

            $gradeEnum = GradeScale::fromPercentage($totalScore);
            $letterGrade = $gradeEnum->value;
            $gradePoint = $gradeEnum->gradePoint();
            $status = $gradeEnum->isPassing() ? 'passed' : ($gradeEnum->canRetake() ? 'retake' : 'failed');
        }

        SemesterGrade::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_id' => $subjectId, // Хатман бояд бошад (unique constraint)
                'semester_id' => $semesterId,
            ],
            [
                'subject_assignment_id' => $subjectAssignmentId,
                'rating1_score' => $rating1,
                'rating2_score' => $rating2,
                'exam_score' => $exam,
                'retake_score' => $retakeScore,
                'total_score' => $totalScore,
                'letter_grade' => $letterGrade,
                'grade_point' => $gradePoint,
                'status' => $status,
                'is_finalized' => true,
                'finalized_at' => now(),
            ]
        );

        $debtDetector = app(\App\Services\DebtDetector::class);
        if ($totalScore !== null && $totalScore < 50) {
            $semesterGrade = SemesterGrade::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->first();
            if ($semesterGrade) {
                $debtDetector->checkAndCreateDebt($semesterGrade);
            }
        } elseif ($totalScore !== null && $totalScore >= 50 && $letterGrade !== null) {
            $debtDetector->resolveDebtAfterRetake($studentId, $subjectId, $semesterId, $totalScore, $letterGrade);
        }
    }

    /**
     * НАВ: Имтиҳон аз ТЕСТИ ОНЛАЙН (автоматӣ, на дастӣ)
     * main → retake → retake2 (агар такрор супорида бошад, ҳамон ҳисоб мешавад)
     */
    public function calculateExamPercentage(int $studentId, int $subjectAssignmentId, int $semesterId, string $examType = 'main'): float
    {
        $exam = $this->findExam($subjectAssignmentId, $semesterId, $examType === 'main' ? 'main' : 'all');

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

        $maxPoints = $this->examMaxPoints[$exam->id]
            ??= (float) $exam->examQuestions()->sum('points');
        if ($maxPoints <= 0) {
            $maxPoints = (float) $exam->total_questions_count * 2.5;
        }

        if ($maxPoints > 0) {
            return round(min(100, ($attempt->total_score / $maxPoints) * 100), 2);
        }

        return 0;
    }

    public function calculateExamScore(int $studentId, int $subjectAssignmentId, int $semesterId): ?float
    {
        $exam = $this->findExam($subjectAssignmentId, $semesterId, 'main');

        if (!$exam) {
            return null;
        }

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'auto_submitted', 'graded'])
            ->orderByDesc('submitted_at')
            ->first();

        return $attempt?->total_score !== null ? (float) $attempt->total_score : null;
    }

    private function findExam(int $subjectAssignmentId, int $semesterId, string $examType): ?Exam
    {
        $key = $subjectAssignmentId . ':' . $semesterId . ':' . $examType;
        if (!array_key_exists($key, $this->exams)) {
            $query = Exam::where('subject_assignment_id', $subjectAssignmentId)
                ->where('semester_id', $semesterId);

            if ($examType === 'main') {
                $query->where('exam_type', 'main');
            } elseif ($examType === 'all') {
                $query->whereIn('exam_type', ['main', 'retake', 'retake_commission']);
            } else {
                $query->where('exam_type', $examType);
            }

            $this->exams[$key] = $query->latest('starts_at')->first();
        }

        return $this->exams[$key];
    }
}
