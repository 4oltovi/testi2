<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SemesterGrade;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;

        $grades = SemesterGrade::where('student_id', $student?->id)
            ->where('is_finalized', true)
            ->with(['curriculum.subject', 'semester'])
            ->orderBy('semester_id')
            ->get();

        return view('student.transcript.index', compact('grades', 'student'));
    }

    public function download(Request $request)
    {
        return back()->with('info', 'Содироти PDF дар версияи оянда фаъол мешавад.');
    }
}
