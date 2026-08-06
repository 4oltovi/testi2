<?php

namespace App\Services;

use App\Enums\GradeScale;
use App\Models\CategoryScore;
use App\Models\CurrentGrade;
use App\Models\GradeCategorySetting;
use App\Models\SemesterGrade;
use App\Models\Setting;
use App\Models\SubjectAssignment;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

/**
 * Хидмати ҳисоби баҳоҳо
 *
 * Мутобиқи низоми кредитии Тоҷикистон:
 * Формулаи стандарт (бе КМ): R1 × W1 + R2 × W2 + Exam × WE
 * Формулаи бо КМ: R1 × W1 + R2 × W2 + КМ × WI + Exam × WE
 *
 * Ҳамаи коэффисиентҳо аз Settings танзимшаванда
 */
class GradeCalculator
{
    private const CACHE_TTL = 300; // 5 дақиқа

    /**
     * Гирифтани коэффисиентҳо аз Settings
     */
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
     * Ҳисоби рейтинги 1 (аз current_grades) — бо кеш
     */
    public function calculateRating1(int $studentId, int $subjectAssignmentId, int $semesterId): float
    {
        $cacheKey = "rating1:{$studentId}:{$subjectAssignmentId}:{$semesterId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($studentId, $subjectAssignmentId, $semesterId) {
            $weekStart = (int) Setting::get('rating1_week_start', 1);
            $weekEnd = (int) Setting::get('rating1_week_end', 8);

            $grades = CurrentGrade::where('student_id', $studentId)
                ->where('subject_assignment_id', $subjectAssignmentId)
                ->where('semester_id', $semesterId)
                ->whereBetween('week_number', [$weekStart, $weekEnd])
                ->get();

            return $this->calculateAverageScore($grades);
        });
    }

    /**
     * Ҳисоби рейтинги 2 (аз current_grades) — бо кеш
     */
    public function calculateRating2(int $studentId, int $subjectAssignmentId, int $semesterId): float
    {
        $cacheKey = "rating2:{$studentId}:{$subjectAssignmentId}:{$semesterId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($studentId, $subjectAssignmentId, $semesterId) {
            $weekStart = (int) Setting::get('rating2_week_start', 9);
            $weekEnd = (int) Setting::get('rating2_week_end', 16);

            $grades = CurrentGrade::where('student_id', $studentId)
                ->where('subject_assignment_id', $subjectAssignmentId)
                ->where('semester_id', $semesterId)
                ->whereBetween('week_number', [$weekStart, $weekEnd])
                ->get();

            return $this->calculateAverageScore($grades);
        });
    }

    /**
     * Ҳисоби рейтинг аз category_scores (системаи нав бо 5 категория) — бо кеш
     */
    public function calculateRatingFromCategories(int $studentId, int $subjectAssignmentId, int $semesterId, string $period = 'rating1'): float
    {
        $cacheKey = "category_rating:{$period}:{$studentId}:{$subjectAssignmentId}:{$semesterId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($studentId, $subjectAssignmentId, $semesterId, $period) {
            $weekStart = (int) Setting::get("{$period}_week_start", $period === 'rating1' ? 1 : 9);
            $weekEnd = (int) Setting::get("{$period}_week_end", $period === 'rating1' ? 8 : 16);

            $scores = CategoryScore::where('student_id', $studentId)
                ->where('subject_assignment_id', $subjectAssignmentId)
                ->where('semester_id', $semesterId)
                ->get();

            if ($scores->isEmpty()) {
                return 0;
            }

            $totalScore = $scores->sum('score');
            $totalMax = $scores->sum('max_score');

            if ($totalMax == 0) return 0;

            return round(($totalScore / $totalMax) * 100, 2);
        });
    }

    /**
     * Ҳисоби миёнаи баллҳо (аз 100) — системаи кӯҳна
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
     * Ҳисоби баҳои ниҳоӣ бо формулаи барномаи баста
     *
     * total_score = ((rating + journal) / 2) + (exam × 0.50)
     */
    public function calculateFinalGrade(SemesterGrade $semesterGrade, bool $hasIndependentWork = true): array
    {
        $examScore = $semesterGrade->retake2_score
            ?? $semesterGrade->retake_score
            ?? $semesterGrade->exam_score;

        $ratingScore = $semesterGrade->rating1_score;
        $journalScore = $semesterGrade->rating2_score ?? $semesterGrade->independent_work_score ?? null;

        if (is_null($ratingScore) || is_null($journalScore) || is_null($examScore)) {
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

        $combinedScore = (($ratingScore + $journalScore) / 2);
        $totalScore = round($combinedScore + ($examScore * 0.50), 2);
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
                'rating' => $ratingScore,
                'journal' => $journalScore,
                'exam' => $examScore,
            ],
        ];
    }

    /**
     * Автоматикӣ ҳисоб ва сабти баҳои ниҳоӣ
     */
    public function processAndSaveFinalGrade(SemesterGrade $semesterGrade): SemesterGrade
    {
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
            $semesterGrade->credits_earned = $semesterGrade->curriculum?->credits ?? 0;
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
     * Ҳисоби рейтинги донишҷӯ дар як фан
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

        // Баҳоҳои категориявӣ
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
            ->with('curriculum.subject')
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
}
