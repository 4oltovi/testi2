<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\SemesterGpa;
use App\Models\Student;

/**
 * Хидмати ҳисоби GPA мутобиқи низоми кредитии Тоҷикистон
 *
 * Формула: GPA = Σ(Gi × Ci) / Σ(Ci)
 * Ку: Gi = grade point (4.0, 3.67, ...), Ci = кредитҳо
 */
class GpaCalculator
{
    /**
     * Ҳисоби GPA барои як семестр
     */
    public function calculateSemesterGpa(Student $student, Semester $semester): ?SemesterGpa
    {
        // Ҳамаи баҳоҳои тасдиқшуда дар ин семестр
        $grades = SemesterGrade::where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->where('is_finalized', true)
            ->with('subjectAssignment.subject')
            ->get();

        if ($grades->isEmpty()) {
            return null;
        }

        $totalGradePoints = 0;
        $totalCreditsAttempted = 0;
        $totalCreditsEarned = 0;
        $subjectsPassed = 0;
        $subjectsFailed = 0;

        foreach ($grades as $grade) {
            $credits = $grade->subjectAssignment?->credits ?? 0;
            $gradePoint = $grade->grade_point ?? 0;

            $totalCreditsAttempted += $credits;
            $totalGradePoints += ($gradePoint * $credits);

            if ($grade->isPassed()) {
                $totalCreditsEarned += $credits;
                $subjectsPassed++;
            } else {
                $subjectsFailed++;
            }
        }

        // Ҳисоби GPA
        $gpa = $totalCreditsAttempted > 0
            ? round($totalGradePoints / $totalCreditsAttempted, 2)
            : 0;

        // Ҳисоби GPA кумулятивӣ
        $cumulativeResult = $this->calculateCumulativeGpa($student, $semester);
        $cumulativeGpa = $cumulativeResult['gpa'];

        // Сохтан ё навсозии SemesterGpa
        $semesterGpa = SemesterGpa::updateOrCreate(
            [
                'student_id' => $student->id,
                'semester_id' => $semester->id,
            ],
            [
                'gpa' => $gpa,
                'cumulative_gpa' => $cumulativeGpa,
                'credits_attempted' => $totalCreditsAttempted,
                'credits_earned' => $totalCreditsEarned,
                'subjects_passed' => $subjectsPassed,
                'subjects_failed' => $subjectsFailed,
                'calculated_at' => now(),
            ]
        );

        return $semesterGpa;
    }

    /**
     * Ҳисоби GPA кумулятивӣ (аз аввал то ин семестр)
     */
    public function calculateCumulativeGpa(Student $student, Semester $upToSemester): array
    {
        // Ҳамаи баҳоҳои тасдиқшуда то ин семестр
        $allGrades = SemesterGrade::where('student_id', $student->id)
            ->where('is_finalized', true)
            ->whereHas('semester', function ($query) use ($upToSemester) {
                $query->where('start_date', '<=', $upToSemester->end_date);
            })
            ->with('subjectAssignment.subject')
            ->get();

        if ($allGrades->isEmpty()) {
            return ['gpa' => 0, 'credits' => 0];
        }

        $totalGradePoints = 0;
        $totalCreditsAttempted = 0;
        $totalCreditsEarned = 0;

        // Барои фанҳои такрорсупорӣ — танҳо охирин баҳоро мегирем
        $latestGrades = $this->getLatestGradesPerSubject($allGrades);

        foreach ($latestGrades as $grade) {
            $credits = $grade->subjectAssignment?->subject?->credits ?? 0;
            $gradePoint = $grade->grade_point ?? 0;

            $totalCreditsAttempted += $credits;
            $totalGradePoints += ($gradePoint * $credits);

            if ($grade->isPassed()) {
                $totalCreditsEarned += $credits;
            }
        }

        $gpa = $totalCreditsAttempted > 0
            ? round($totalGradePoints / $totalCreditsAttempted, 2)
            : 0;

        return [
            'gpa' => $gpa,
            'credits' => $totalCreditsEarned,
        ];
    }

    /**
     * Охирин баҳо барои ҳар як фан (агар такрорсупорӣ бошад)
     */
    private function getLatestGradesPerSubject($grades)
    {
        // Гурӯҳбандӣ аз рӯйи фан (subject_id)
        $grouped = $grades->groupBy(function ($grade) {
            return $grade->subjectAssignment?->subject_id ?? 0;
        });

        // Барои ҳар як фан, охирин баҳоро мегирем
        return $grouped->map(function ($subjectGrades) {
            return $subjectGrades->sortBy('semester_id')->last();
        })->values();
    }

    /**
     * Муайян кардани дараҷаи ифтихорӣ
     */
    public function determineHonors(?float $gpa): string
    {
        if ($gpa === null) {
            return 'none';
        }

        if ($gpa >= 3.90) {
            return 'summa_cum_laude';
        }

        if ($gpa >= 3.70) {
            return 'magna_cum_laude';
        }

        if ($gpa >= 3.50) {
            return 'cum_laude';
        }

        return 'none';
    }

    /**
     * Номи дараҷаи ифтихорӣ ба забони тоҷикӣ
     */
    public static function honorsLabel(string $honors): string
    {
        return match ($honors) {
            'summa_cum_laude' => 'Аъло (Summa Cum Laude)',
            'magna_cum_laude' => 'Аъло (Magna Cum Laude)',
            'cum_laude' => 'Аъло (Cum Laude)',
            default => 'Оддӣ',
        };
    }
}
