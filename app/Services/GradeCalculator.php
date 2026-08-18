<?php

namespace App\Services;

use App\Enums\GradeScale;
use App\Models\CategoryScore;
use App\Models\CurrentGrade;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Setting;
use App\Models\SubjectAssignment;
use App\Models\Student;
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
        // Аз танзимот: 60 (ё дилхоҳ)
        $journalMax = (float) Setting::get('journal_part_points', 60);
        $testMax = max(0, 100 - $journalMax); // 40

        // Фоизи журнал (0-100) → ба 60 оварда мешавад
        $journalPct = $this->calculateJournalPercentage($studentId, $subjectAssignmentId, $semesterId, $period);

        // Фоизи тест (0-100) → ба 40 оварда мешавад
        $testPct = $this->calculateTestPercentage($studentId, $subjectAssignmentId, $semesterId, $period);

        return round(($journalPct / 100 * $journalMax) + ($testPct / 100 * $testMax), 2);
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
            $total = $scores->sum('score');
            $max = $scores->sum('max_score');
            return $max > 0 ? round(($total / $max) * 100, 2) : 0;
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
            ->get();

        if ($scores->isEmpty()) return 0;

        $totalScore = $scores->sum('score');
        $totalMax = $scores->sum('max_score');

        if ($totalMax == 0) return 0;

        return round(($totalScore / $totalMax) * 100, 2);
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
        $examScore = $semesterGrade->retake2_score
            ?? $semesterGrade->retake_score
            ?? $semesterGrade->exam_score;

        $rating1 = (float) ($semesterGrade->rating1_score ?? 0);
        $rating2 = (float) ($semesterGrade->rating2_score ?? 0);

        if (is_null($examScore)) {
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

        // Аз танзимот (қобили тағйир):
        $divisor = (float) \App\Models\Setting::get('rating_part_divisor', 4);    // (R1+R2) ÷ 4
        $examWeight = (float) \App\Models\Setting::get('exam_weight', 0.5);       // Имтиҳон × 0,5

        $totalScore = round(($rating1 + $rating2) / $divisor + ($examScore * $examWeight), 2);

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
            $pct = $this->calculateExamPercentage(
                $semesterGrade->student_id,
                $semesterGrade->subject_assignment_id,
                $semesterGrade->semester_id
            );
            if ($pct > 0) {
                $semesterGrade->exam_score = $pct;
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
     * НАВ: Имтиҳон аз ТЕСТИ ОНЛАЙН (автоматӣ, на дастӣ)
     * main → retake → retake2 (агар такрор супорида бошад, ҳамон ҳисоб мешавад)
     */
    public function calculateExamPercentage(int $studentId, int $subjectAssignmentId, int $semesterId): float
    {
        $exam = Exam::where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->whereIn('exam_type', ['main', 'retake', 'retake_commission'])
            ->latest('starts_at')
            ->first();

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
