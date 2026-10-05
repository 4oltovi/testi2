<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SemesterGrade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    /**
     * Баҳои тасдиқшудаи донишҷӯ бо маълумоти кредит.
     *
     * `subjectAssignment.subject` ва `subjectAssignment.group` бор карда
     * мешаванд: иу аввал барои атрибути `subject_credits`, дуюм барои рақами
     * гурӯҳ дар ҳар сатри PDF — бо ин дархости иловагӣ нафрӯ накунад.
     */
    private function gradesFor($student)
    {
        return SemesterGrade::where('student_id', $student?->id)
            ->where('is_finalized', true)
            ->with(['subject', 'semester', 'subjectAssignment.subject', 'subjectAssignment.group'])
            ->orderBy('semester_id')
            ->get();
    }

    public function index(Request $request)
    {
        $student = $request->user()->student;

        $grades = $this->gradesFor($student);

        return view('student.transcript.index', compact('grades', 'student'));
    }

    public function download(Request $request)
    {
        $student = $request->user()->student;

        $grades = $this->gradesFor($student);

        $pdf = Pdf::loadView('student.transcript.pdf', compact('grades', 'student'));
        $pdf->setPaper('a4');

        return $pdf->stream('transcript_' . ($student?->id ?? 'student') . '.pdf');
    }
}
