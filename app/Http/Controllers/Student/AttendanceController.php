<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $records = DB::table('daily_attendance')
                ->where('student_id', $student->id)
                ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
                ->select('daily_attendance.*', 'groups.name as group_name')
                ->orderByDesc('daily_attendance.attendance_date')
                ->paginate(50);

            $summary = DB::table('daily_attendance')
                ->where('student_id', $student->id)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent
                ')
                ->first();
        }

        return view('student.attendance', compact('student', 'semester', 'records', 'summary'));
    }
}
