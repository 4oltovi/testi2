<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;
use App\Services\GradeCalculator;

class GradeController extends Controller
{
    /**
     * Як сатр барои як ФАН дар семестр (ҳатто агар як фан якчанд
     * таъинот дошта бошад — лексия/амалӣӣ/лабораторӣ → як сатр).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\SubjectAssignment>  $assignments
     * @return array<string, mixed>
     */
    private function buildSubjectRow(Student $student, $assignments, $gradeCalc): array
    {
        // Таъиноти асосӣ: он ки semester_grade дорад, вагарна аввалин
        $primary = $assignments->first(fn ($a) => SemesterGrade::where('student_id', $student->id)
            ->where('subject_assignment_id', $a->id)
            ->where('semester_id', $a->semester_id)
            ->exists()) ?? $assignments->first();

        $semester = $primary->semester;

        // Кредит: ҷамъи майдонҳои аслӣ; агар ҳама null → ҳалли (subject)
        $rawCredits = $assignments
            ->map(fn ($a) => $a->getRawOriginal('credits'))
            ->filter(fn ($v) => $v !== null);
        $credits = $rawCredits->isNotEmpty()
            ? (int) $rawCredits->sum()
            : $primary->credits;

        // R1/R2: ҷамъи журналҳо (ҳадди ягона) + тести компютерӣ як маротиба
        $journalMax = (float) \App\Models\Setting::get('journal_part_points', 60);

        $journal1 = 0.0;
        $journal2 = 0.0;
        foreach ($assignments as $assignment) {
            $journal1 += $gradeCalc->calculateJournalPercentage($student->id, $assignment->id, $assignment->semester_id, 'rating1');
            $journal2 += $gradeCalc->calculateJournalPercentage($student->id, $assignment->id, $assignment->semester_id, 'rating2');
        }

        $computerRating1 = $gradeCalc->calculateComputerRatingScore($student->id, $primary->id, $primary->semester_id, 'rating1');
        $computerRating2 = $gradeCalc->calculateComputerRatingScore($student->id, $primary->id, $primary->semester_id, 'rating2');

        $rating1 = round(min($journal1, $journalMax) + $computerRating1, 2);
        $rating2 = round(min($journal2, $journalMax) + $computerRating2, 2);

        // Имтиҳон: беҳтарин аз ҳамаи таъинотҳои ин фан
        $exam = null;
        foreach ($assignments as $assignment) {
            $score = $gradeCalc->calculateExamScore($student->id, $assignment->id, $assignment->semester_id);
            if ($score !== null && ($exam === null || $score > $exam)) {
                $exam = $score;
            }
        }

        // Сатри баҳои семестр: тасдиқшуда афзалият дорад
        $semesterGrade = $assignments
            ->map(fn ($a) => SemesterGrade::where('student_id', $student->id)
                ->where('subject_assignment_id', $a->id)
                ->where('semester_id', $a->semester_id)
                ->first())
            ->filter()
            ->sortByDesc('is_finalized')
            ->first();

        $retakeScore = null;
        $retakeExam = \App\Models\RetakeExam::where('subject_id', $primary->subject_id)
            ->where('semester_id', $primary->semester_id)
            ->first();

        if ($retakeExam) {
            // Кӯшишҳои гуногун — беҳтарин (калонтарин) натиҷа
            $bestScore = \App\Models\RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                ->where('student_id', $student->id)
                ->max('score');

            if ($bestScore !== null) {
                $retakeScore = (float) $bestScore;
            }
        }

        $effectiveExamScore = $retakeScore !== null ? $retakeScore : ($exam ?? 0);

        $totalScore = null;
        $letterGrade = null;
        $gradePoint = null;
        $status = null;

        if ($effectiveExamScore > 0 || ($rating1 > 0 || $rating2 > 0)) {
            $totalScore = round(($rating1 + $rating2) / 4 + ($effectiveExamScore * 0.5), 2);

            $gradeEnum = \App\Enums\GradeScale::fromPercentage($totalScore);
            $letterGrade = $gradeEnum->value;
            $gradePoint = $gradeEnum->gradePoint();
            $status = $gradeEnum->isPassing() ? 'passed' : ($gradeEnum->canRetake() ? 'retake' : 'failed');
        }

        return [
            'subject_assignment' => $primary,
            'subject' => $primary->subject,
            // Манбаъи ягонаи кредит — ҷамъи ҳамаи таъинотҳои ин фан
            'credits' => $credits,
            'semester' => $semester,
            'semester_grade' => $semesterGrade,
            'rating1' => $rating1,
            'rating2' => $rating2,
            'computer_rating1' => $computerRating1,
            'computer_rating2' => $computerRating2,
            'exam' => $exam,
            // Баҳои имтиҳони такрорӣ (агар гузашта бошад)
            'retake_score' => $retakeScore,
            'total_score' => $totalScore,
            'letter_grade' => $letterGrade,
            'grade_point' => $gradePoint,
            'status' => $status,
        ];
    }

    public function index(Request $request)
    {
        $student = $request->user()->student;
        $currentSemester = Semester::current();
        $currentYearId = $currentSemester?->academic_year_id;

        if (!$student || !$student->group_id) {
            return view('student.grades.index', compact('grades', 'currentSemester', 'student', 'currentYearId'));
        }

        $group = Group::find($student->group_id);

        // Танҳо таъинотҳои обуданашуда; як фан = як сатр
        $subjectAssignments = SubjectAssignment::where('group_id', $student->group_id)
            ->notMerged()
            ->with(['subject', 'semester'])
            ->orderByDesc('semester_id')
            ->get();

        $gradeCalc = app(GradeCalculator::class);

        $grades = $subjectAssignments->groupBy('semester_id')->map(function ($assignments) use ($student, $gradeCalc) {
            // Як фан дар як семестр = як сатр (лекция/амалӣӣ/лабораторӣ муттаҳид)
            return $assignments->groupBy('subject_id')->map(
                fn ($subjectGroup) => $this->buildSubjectRow($student, $subjectGroup, $gradeCalc)
            );
        })->groupBy(function ($semGrades, $semesterId) {
            $sem = $semGrades->first()['semester'] ?? null;
            return $sem?->academic_year_id ?? $semesterId;
        })->map(function ($yearGroup) {
            return $yearGroup->mapWithKeys(function ($semGrades) {
                $sem = $semGrades->first()['semester'] ?? null;
                $semesterId = $sem?->id ?? 'unknown';
                return [$semesterId => $semGrades];
            });
        });

        return view('student.grades.index', compact('grades', 'currentSemester', 'student', 'currentYearId'));
    }

    public function semester(Semester $semester, Request $request)
    {
        $student = $request->user()->student;

        if (!$student || !$student->group_id) {
            return view('student.grades.semester', compact('grades', 'semester', 'student'));
        }

        $subjectAssignments = SubjectAssignment::where('group_id', $student->group_id)
            ->where('semester_id', $semester->id)
            ->notMerged()
            ->with(['subject'])
            ->get();

        $gradeCalc = app(GradeCalculator::class);

        $grades = $subjectAssignments
            ->groupBy('subject_id')
            ->map(fn ($subjectGroup) => $this->buildSubjectRow($student, $subjectGroup, $gradeCalc));

        return view('student.grades.semester', compact('grades', 'semester', 'student'));
    }
}
