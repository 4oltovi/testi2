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

        if (!$student || !$student->group_id) {
            return view('student.grades.index', compact('grades', 'currentSemester', 'student'));
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
                $exam = $gradeCalc->calculateExamPercentage($student->id, $assignment->id, $assignment->semester_id);

                $totalScore = null;
                $letterGrade = null;
                $gradePoint = null;
                $status = null;

                if ($exam > 0 || ($rating1 > 0 || $rating2 > 0)) {
                    $divisor = (float) \App\Models\Setting::get('rating_part_divisor', 4);
                    $examWeight = (float) \App\Models\Setting::get('exam_weight', 0.5);

                    $r1 = (float) $rating1;
                    $r2 = (float) $rating2;

                    $totalScore = round(($r1 + $r2) / $divisor + ($exam * $examWeight), 2);

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
                    'exam' => $exam,
                    'total_score' => $totalScore,
                    'letter_grade' => $letterGrade,
                    'grade_point' => $gradePoint,
                    'status' => $status,
                ];
            });
        });

        return view('student.grades.index', compact('grades', 'currentSemester', 'student'));
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
            $exam = $gradeCalc->calculateExamPercentage($student->id, $assignment->id, $semester->id);

            $totalScore = null;
            $letterGrade = null;
            $gradePoint = null;
            $status = null;

            if ($exam > 0 || ($rating1 > 0 || $rating2 > 0)) {
                $divisor = (float) \App\Models\Setting::get('rating_part_divisor', 4);
                $examWeight = (float) \App\Models\Setting::get('exam_weight', 0.5);

                $r1 = (float) $rating1;
                $r2 = (float) $rating2;

                $totalScore = round(($r1 + $r2) / $divisor + ($exam * $examWeight), 2);

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
