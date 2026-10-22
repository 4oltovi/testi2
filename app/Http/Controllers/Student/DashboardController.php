<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;
        $semester = Semester::current();

        $data = [
            'student' => $student,
            'semester' => $semester,
            'gpa' => $student?->cumulative_gpa ?? 0,
            'credits' => 0,
            'debts_count' => 0,
            'grades' => collect(),
            // Сабти давомот нест -> null, то ки «—» нишон дода шавад (на 100%)
            'attendance_percentage' => null,
            'recent_exams' => collect(),
        ];

        if ($student && $semester) {
            $data['debts_count'] = $student->activeDebts()->count();
            // Як фан = як сатр (лекция/амалӣӣ/лабораторӣ муттаҳид);
            // тасдиқшуда ё баллтарин сатр афзалият дорад
            $data['grades'] = SemesterGrade::where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->with('subjectAssignment.subject')
                ->get()
                ->groupBy('subject_id')
                ->map(fn ($rows) => $rows->sortByDesc('is_finalized')->first())
                ->values();
            $data['attendance_percentage'] = $student->getAttendancePercentage($semester->id);

            // Ҷамъи кредитҳои фанҳои семестри ҷорӣ (ҳама, новобаста аз баҳо)
            $subjectAssignments = SubjectAssignment::where('group_id', $student->group_id)
                ->where('semester_id', $semester->id)
                ->notMerged()
                ->with('subject')
                ->get()
                ->groupBy('subject_id');
            $data['credits'] = (int) $subjectAssignments->map(function ($group) {
                $raw = $group->map(fn ($a) => $a->getRawOriginal('credits'))->filter(fn ($v) => $v !== null);
                return $raw->isNotEmpty() ? (int) $raw->sum() : (int) ($group->first()->credits ?? 0);
            })->sum();

            // Натиҷаҳои охирини тестҳо
            $data['recent_exams'] = \App\Models\ExamAttempt::where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'auto_submitted', 'graded'])
                ->with(['exam.subjectAssignment.subject', 'exam.group'])
                ->orderByDesc('submitted_at')
                ->limit(5)
                ->get();
        }

        return view('student.dashboard', $data);
    }
}
