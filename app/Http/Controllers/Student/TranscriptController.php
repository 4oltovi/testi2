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
        $rows = SemesterGrade::where('student_id', $student?->id)
            ->where('is_finalized', true)
            ->with(['subject', 'semester', 'subjectAssignment.subject', 'subjectAssignment.group'])
            ->orderBy('semester_id')
            ->get();

        // Як фан дар як семестр = як сатр; кредитҳои ҷудошуда ҷамъ мешаванд
        return $rows
            ->groupBy(fn ($g) => $g->subject_id . '_' . $g->semester_id)
            ->map(function ($group) {
                if ($group->count() === 1) {
                    return $group->first();
                }

                $representative = $group->first();
                // Ҷамъи кредитҳои ҳамаи таъинотҳои ин фан (лекция+амалӣӣ+…)
                $representative->subject_credits_override = (int) $group->sum(
                    fn ($g) => (int) ($g->subjectAssignment?->getRawOriginal('credits') ?? $g->subject_credits)
                );

                return $representative;
            })
            ->values();
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
