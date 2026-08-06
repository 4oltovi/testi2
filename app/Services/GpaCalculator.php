<?php

namespace App\Services;

use App\Enums\GradeScale;
use App\Models\Curriculum;
use App\Models\Semester;
use App\Models\SemesterGpa;
use App\Models\SemesterGrade;
use App\Models\Student;
use Illuminate\Support\Collection;

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
            ->with('curriculum')
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
            $credits = $grade->curriculum?->credits ?? 0;
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

        // GPA = Σ(Gi × Ci) / Σ(Ci)
        $gpa = $totalCreditsAttempted > 0
            ? round($totalGradePoints / $totalCreditsAttempted, 2)
            : 0;

        // GPA кумулятивӣ
        $cumulativeData = $this->calculateCumulativeGpa($student, $semester);

        // Сабт ё навсозии SemesterGpa
        $semesterGpa = SemesterGpa::updateOrCreate(
            ['student_id' => $student->id, 'semester_id' => $semester->id],
            [
                'academic_year_id' => $semester->academic_year_id,
                'gpa' => $gpa,
                'credits_attempted' => $totalCreditsAttempted,
                'credits_earned' => $totalCreditsEarned,
                'subjects_passed' => $subjectsPassed,
                'subjects_failed' => $subjectsFailed,
                'total_grade_points' => $totalGradePoints,
                'total_subjects' => $grades->count(),
                'cumulative_gpa' => $cumulativeData['gpa'],
                'cumulative_credits_earned' => $cumulativeData['credits'],
            ]
        );

        // Навсозии GPA дар профили донишҷӯ
        $student->update([
            'cumulative_gpa' => $cumulativeData['gpa'],
            'total_credits_earned' => $cumulativeData['credits'],
        ]);

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
            ->with('curriculum')
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
            $credits = $grade->curriculum?->credits ?? 0;
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
     * Ҳисоби GPA барои тамоми донишҷӯёни як гурӯҳ
     */
    public function calculateGroupGpa(int $groupId, int $semesterId): Collection
    {
        $semester = Semester::find($semesterId);
        $students = Student::where('group_id', $groupId)->active()->get();

        $results = collect();

        foreach ($students as $student) {
            $gpa = $this->calculateSemesterGpa($student, $semester);
            $results->push([
                'student' => $student,
                'semester_gpa' => $gpa,
            ]);
        }

        return $results->sortByDesc(fn($item) => $item['semester_gpa']?->gpa ?? 0);
    }

    /**
     * Охирин баҳо барои ҳар фан (агар такрорсупорӣ бошад)
     */
    private function getLatestGradesPerSubject(Collection $grades): Collection
    {
        return $grades->groupBy(fn($g) => $g->curriculum_id)
            ->map(function ($subjectGrades) {
                // Агар гузашт — баҳои гузаштаро мегирем
                $passed = $subjectGrades->where('status', 'passed')->sortByDesc('finalized_at')->first();
                if ($passed) return $passed;

                // Вагарна — охирин баҳоро
                return $subjectGrades->sortByDesc('finalized_at')->first();
            })
            ->filter()
            ->values();
    }

    /**
     * Муайян кардани ифтихорот (honors) аз рӯйи GPA
     */
    public function determineHonors(float $gpa): string
    {
        return match (true) {
            $gpa >= 3.80 => 'summa_cum_laude', // Бо ифтихори аъло
            $gpa >= 3.50 => 'magna_cum_laude', // Бо ифтихор
            $gpa >= 3.00 => 'cum_laude',       // Қобили таваҷҷуҳ
            default => 'none',
        };
    }

    /**
     * Тарҷумаи honors ба тоҷикӣ
     */
    public static function honorsLabel(string $honors): string
    {
        return match ($honors) {
            'summa_cum_laude' => 'Бо ифтихори аъло (Диплом бо имтиёз)',
            'magna_cum_laude' => 'Бо ифтихор',
            'cum_laude' => 'Қобили таваҷҷуҳ',
            default => '',
        };
    }
}
