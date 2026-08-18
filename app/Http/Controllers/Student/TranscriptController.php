<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SemesterGrade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;

        $grades = SemesterGrade::where('student_id', $student?->id)
            ->where('is_finalized', true)
            ->with(['subject', 'semester'])
            ->orderBy('semester_id')
            ->get();

        return view('student.transcript.index', compact('grades', 'student'));
    }

    public function download(Request $request)
    {
        $student = $request->user()->student;

        $grades = SemesterGrade::where('student_id', $student?->id)
            ->where('is_finalized', true)
            ->with(['subject', 'semester'])
            ->orderBy('semester_id')
            ->get();

        $pdf = Pdf::loadView('student.transcript.pdf', compact('grades', 'student'));
        $pdf->setPaper('a4');

        return $pdf->stream('transcript_' . ($student?->id ?? 'student') . '.pdf');
    }
}
