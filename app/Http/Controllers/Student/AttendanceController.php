<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;
        $semester = Semester::current();

        $records = collect();
        $summary = null;

        if ($student && $semester) {
            $records = Attendance::where('student_id', $student->id)
                ->whereHas('subjectAssignment', fn($q) => $q->where('semester_id', $semester->id))
                ->with(['subjectAssignment.subject', 'subjectAssignment.teacher.user'])
                ->orderByDesc('attendance_date')
                ->paginate(50);

            $summary = Attendance::where('student_id', $student->id)
                ->whereHas('subjectAssignment', fn($q) => $q->where('semester_id', $semester->id))
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ("present", "late", "excused", "sick") THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent
                ')
                ->first();
        }

        return view('student.attendance', compact('student', 'semester', 'records', 'summary'));
    }
}
