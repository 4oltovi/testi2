<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\SemesterGrade;
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
            'debts_count' => 0,
            'grades' => collect(),
            'attendance_percentage' => 100,
            'recent_exams' => collect(),
        ];

        if ($student && $semester) {
            $data['debts_count'] = $student->activeDebts()->count();
            $data['grades'] = SemesterGrade::where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->with('subject')
                ->get();
            $data['attendance_percentage'] = $student->getAttendancePercentage($semester->id);

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
