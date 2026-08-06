<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $currentSemester = Semester::current();

        $stats = Cache::remember('report_stats', 300, function () {
            return [
                'total_students' => Student::active()->count(),
                'total_teachers' => Teacher::active()->count(),
                'total_groups' => Group::active()->count(),
                'total_faculties' => Faculty::active()->count(),
                'total_debtors' => Student::withDebts()->count(),
                'active_debts' => AcademicDebt::open()->count(),
            ];
        });

        return view('admin.reports.index', compact('stats', 'currentSemester'));
    }

    public function students(Request $request): View
    {
        $query = Student::with(['user', 'group', 'specialty.department.faculty', 'course'])->active();

        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($facultyId = $request->get('faculty_id')) {
            $query->whereHas('specialty.department', fn($q) => $q->where('faculty_id', $facultyId));
        }

        $students = $query->orderBy('id')->paginate(50)->withQueryString();
        $groups = Group::active()->orderBy('name')->get();
        $faculties = Faculty::active()->get();

        return view('admin.reports.students', compact('students', 'groups', 'faculties'));
    }

    public function debtors(Request $request): View
    {
        $query = AcademicDebt::with(['student.user', 'student.group', 'subject', 'semester'])->open();

        if ($groupId = $request->get('group_id')) {
            $query->whereHas('student', fn($q) => $q->where('group_id', $groupId));
        }

        $debts = $query->orderByDesc('debt_date')->paginate(30)->withQueryString();
        $groups = Group::active()->orderBy('name')->get();

        // Омор
        $debtorsByGroup = AcademicDebt::whereIn('academic_debts.status', ['active', 'retake_scheduled', 'escalated'])
            ->whereNull('academic_debts.deleted_at')
            ->join('students', 'academic_debts.student_id', '=', 'students.id')
            ->join('groups', 'students.group_id', '=', 'groups.id')
            ->selectRaw('groups.name as group_name, COUNT(DISTINCT students.id) as debtors_count')
            ->groupBy('groups.name')
            ->orderByDesc('debtors_count')
            ->get();

        return view('admin.reports.debtors', compact('debts', 'groups', 'debtorsByGroup'));
    }

    public function attendance(Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $groupId = $request->get('group_id');

        $attendanceData = collect();

        if ($groupId && $semesterId) {
            // Оптимизатсия: як query барои ҳама донишҷӯён (без N+1)
            $studentIds = Student::where('group_id', $groupId)->active()->pluck('id');

            $attendanceStats = Attendance::whereIn('student_id', $studentIds)
                ->whereHas('subjectAssignment', fn($q) => $q->where('semester_id', $semesterId))
                ->selectRaw('student_id, 
                    COUNT(*) as total, 
                    SUM(CASE WHEN status IN ("present", "late", "excused", "sick") THEN 1 ELSE 0 END) as present')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            $students = Student::whereIn('id', $studentIds)->with('user')->get();

            $attendanceData = $students->map(function ($student) use ($attendanceStats) {
                $stats = $attendanceStats->get($student->id);
                $total = $stats?->total ?? 0;
                $present = $stats?->present ?? 0;

                return [
                    'student_name' => $student->user?->full_name,
                    'total' => $total,
                    'present' => $present,
                    'absent' => $total - $present,
                    'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 100,
                ];
            })->sortBy('percentage');
        }

        $groups = Group::active()->orderBy('name')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('admin.reports.attendance', compact('attendanceData', 'groups', 'semesters', 'semesterId', 'groupId'));
    }

    public function gpa(Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);

        $gpaData = Student::active()
            ->with(['user', 'group'])
            ->orderByDesc('cumulative_gpa')
            ->paginate(50)
            ->withQueryString();

        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('admin.reports.gpa', compact('gpaData', 'semesters', 'semesterId'));
    }

    public function examResults(Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $groupId = $request->get('group_id');

        $results = collect();

        if ($semesterId) {
            $query = SemesterGrade::where('semester_id', $semesterId)
                ->where('is_finalized', true)
                ->with(['student.user', 'student.group', 'curriculum.subject']);

            if ($groupId) {
                $query->whereHas('student', fn($q) => $q->where('group_id', $groupId));
            }

            $results = $query->orderBy('student_id')->paginate(50)->withQueryString();
        }

        $groups = Group::active()->orderBy('name')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('admin.reports.exam-results', compact('results', 'groups', 'semesters', 'semesterId', 'groupId'));
    }

    public function export(string $type, Request $request)
    {
        // Placeholder — бо maatwebsite/excel ё dompdf кор мекунад
        return back()->with('info', "Содирот ба {$type} дар дасти кор аст. Дар версияи оянда фаъол мешавад.");
    }
}
