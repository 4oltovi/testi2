<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\SemesterGrade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        $currentSemester = Semester::current();

        $grades = SemesterGrade::where('student_id', $student?->id)
            ->with(['curriculum.subject', 'semester'])
            ->orderByDesc('semester_id')
            ->get()
            ->groupBy('semester_id');

        return view('student.grades.index', compact('grades', 'currentSemester', 'student'));
    }

    public function semester(Semester $semester, Request $request)
    {
        $student = $request->user()->student;

        $grades = SemesterGrade::where('student_id', $student?->id)
            ->where('semester_id', $semester->id)
            ->with(['curriculum.subject'])
            ->get();

        return view('student.grades.semester', compact('grades', 'semester', 'student'));
    }
}
