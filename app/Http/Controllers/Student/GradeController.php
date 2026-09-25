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
    public function index(Request $request)
    {
        $student = $request->user()->student;
        $currentSemester = Semester::current();
        $currentYearId = $currentSemester?->academic_year_id;

        if (!$student || !$student->group_id) {
            return view('student.grades.index', compact('grades', 'currentSemester', 'student', 'currentYearId'));
        }

        $group = Group::find($student->group_id);

        $subjectAssignments = SubjectAssignment::where('group_id', $student->group_id)
            ->with(['subject', 'semester'])
            ->orderByDesc('semester_id')
            ->get();

        $gradeCalc = app(GradeCalculator::class);

        $grades = $subjectAssignments->groupBy('semester_id')->map(function ($assignments) use ($student, $gradeCalc) {
            return $assignments->map(function ($assignment) use ($student, $gradeCalc) {
                $semesterGrade = SemesterGrade::where('student_id', $student->id)
                    ->where('subject_assignment_id', $assignment->id)
                    ->where('semester_id', $assignment->semester_id)
                    ->first();

                $rating1 = $gradeCalc->calculateRating1($student->id, $assignment->id, $assignment->semester_id);
                $rating2 = $gradeCalc->calculateRating2($student->id, $assignment->id, $assignment->semester_id);
                $examPercentage = $gradeCalc->calculateExamPercentage($student->id, $assignment->id, $assignment->semester_id);
                $exam = $gradeCalc->calculateExamScore($student->id, $assignment->id, $assignment->semester_id);
                $computerRating1 = $gradeCalc->calculateComputerRatingScore($student->id, $assignment->id, $assignment->semester_id, 'rating1');
                $computerRating2 = $gradeCalc->calculateComputerRatingScore($student->id, $assignment->id, $assignment->semester_id, 'rating2');

                $retakeScore = null;
                $retakeExam = \App\Models\RetakeExam::where('subject_id', $assignment->subject_id)
                    ->where('semester_id', $assignment->semester_id)
                    ->first();

                if ($retakeExam) {
                    $retakeStudent = \App\Models\RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                        ->where('student_id', $student->id)
                        ->first();

                    if ($retakeStudent && $retakeStudent->score !== null) {
                        $retakeScore = (float) $retakeStudent->score;
                    }
                }

                $effectiveExamScore = $retakeScore !== null ? $retakeScore : ($exam ?? 0);

                $totalScore = null;
                $letterGrade = null;
                $gradePoint = null;
                $status = null;

                if ($effectiveExamScore > 0 || ($rating1 > 0 || $rating2 > 0)) {
                    $r1 = (float) $rating1;
                    $r2 = (float) $rating2;

                    $totalScore = round(($r1 + $r2) / 4 + ($effectiveExamScore * 0.5), 2);

                    $gradeEnum = \App\Enums\GradeScale::fromPercentage($totalScore);
                    $letterGrade = $gradeEnum->value;
                    $gradePoint = $gradeEnum->gradePoint();
                    $status = $gradeEnum->isPassing() ? 'passed' : ($gradeEnum->canRetake() ? 'retake' : 'failed');
                }

                return [
                    'subject_assignment' => $assignment,
                    'subject' => $assignment->subject,
                    'semester' => $assignment->semester,
                    'semester_grade' => $semesterGrade,
                    'rating1' => $rating1,
                    'rating2' => $rating2,
                    'computer_rating1' => $computerRating1,
                    'computer_rating2' => $computerRating2,
                    'exam' => $exam,
                    'total_score' => $totalScore,
                    'letter_grade' => $letterGrade,
                    'grade_point' => $gradePoint,
                    'status' => $status,
                ];
            });
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
            ->with(['subject'])
            ->get();

        $gradeCalc = app(GradeCalculator::class);

        $grades = $subjectAssignments->map(function ($assignment) use ($student, $gradeCalc, $semester) {
            $semesterGrade = SemesterGrade::where('student_id', $student->id)
                ->where('subject_assignment_id', $assignment->id)
                ->where('semester_id', $semester->id)
                ->first();

            $rating1 = $gradeCalc->calculateRating1($student->id, $assignment->id, $semester->id);
            $rating2 = $gradeCalc->calculateRating2($student->id, $assignment->id, $semester->id);
            $examPercentage = $gradeCalc->calculateExamPercentage($student->id, $assignment->id, $semester->id);
            $exam = $gradeCalc->calculateExamScore($student->id, $assignment->id, $semester->id);
            $computerRating1 = $gradeCalc->calculateComputerRatingScore($student->id, $assignment->id, $semester->id, 'rating1');
            $computerRating2 = $gradeCalc->calculateComputerRatingScore($student->id, $assignment->id, $semester->id, 'rating2');

            $retakeScore = null;
            $retakeExam = \App\Models\RetakeExam::where('subject_id', $assignment->subject_id)
                ->where('semester_id', $semester->id)
                ->first();

            if ($retakeExam) {
                $retakeStudent = \App\Models\RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                    ->where('student_id', $student->id)
                    ->first();

                if ($retakeStudent && $retakeStudent->score !== null) {
                    $retakeScore = (float) $retakeStudent->score;
                }
            }

            $effectiveExamScore = $retakeScore !== null ? $retakeScore : ($exam ?? 0);

            $totalScore = null;
            $letterGrade = null;
            $gradePoint = null;
            $status = null;

            if ($effectiveExamScore > 0 || ($rating1 > 0 || $rating2 > 0)) {
                $r1 = (float) $rating1;
                $r2 = (float) $rating2;

                $totalScore = round(($r1 + $r2) / 4 + ($effectiveExamScore * 0.5), 2);

                $gradeEnum = \App\Enums\GradeScale::fromPercentage($totalScore);
                $letterGrade = $gradeEnum->value;
                $gradePoint = $gradeEnum->gradePoint();
                $status = $gradeEnum->isPassing() ? 'passed' : ($gradeEnum->canRetake() ? 'retake' : 'failed');
            }

            return [
                'subject_assignment' => $assignment,
                'subject' => $assignment->subject,
                'semester' => $semester,
                'semester_grade' => $semesterGrade,
                'rating1' => $rating1,
                'rating2' => $rating2,
                'computer_rating1' => $computerRating1,
                'computer_rating2' => $computerRating2,
                'exam' => $exam,
                'total_score' => $totalScore,
                'letter_grade' => $letterGrade,
                'grade_point' => $gradePoint,
                'status' => $status,
            ];
        });

        return view('student.grades.semester', compact('grades', 'semester', 'student'));
    }
}
