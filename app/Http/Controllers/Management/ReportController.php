<?php

namespace App\Http\Controllers\Management;

use App\Exports\AttendanceExport;
use App\Exports\DebtorsExport;
use App\Exports\ExamResultsExport;
use App\Exports\GpaExport;
use App\Exports\StudentsExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\Teacher;
use App\Traits\ResolvesDeanFaculty;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(): View
    {
        $facultyId = $this->facultyId();
        $currentSemester = Semester::current();

        $stats = [
            'total_students' => Student::where('status', 'active')
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))->count(),
            'total_teachers' => Teacher::where('status', 'active')
                ->whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId))->count(),
            'total_groups' => Group::where('is_active', true)
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))->count(),
            'total_faculties' => $facultyId ? 1 : 0,
            'total_debtors' => Student::where('has_debts', true)
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))->count(),
            'active_debts' => AcademicDebt::whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
                )->count(),
        ];

        return view('management.reports.index', compact('stats', 'currentSemester'));
    }

    public function students(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = Student::with(['user', 'group', 'specialty.faculty', 'course'])->active()
            ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($orphanType = $request->get('orphan_type')) {
            $query->where('orphan_type', $orphanType);
        }

        $students = $query->orderBy('id')->paginate(50)->withQueryString();
        $groups = Group::whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();

        return view('management.reports.students', compact('students', 'groups'));
    }

    public function debtors(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = AcademicDebt::with(['student.user', 'student.group', 'subject', 'semester'])
            ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
            );

        if ($groupId = $request->get('group_id')) {
            $query->whereHas('student', fn ($q) => $q->where('group_id', $groupId));
        }

        $debts = $query->orderByDesc('debt_date')->paginate(30)->withQueryString();
        $groups = Group::whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();

        $debtorsByGroup = AcademicDebt::whereIn('status', ['active', 'retake_scheduled', 'escalated'])
            ->whereNull('deleted_at')
            ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
            )
            ->join('students', 'academic_debts.student_id', '=', 'students.id')
            ->join('groups', 'students.group_id', '=', 'groups.id')
            ->selectRaw('groups.name as group_name, COUNT(DISTINCT students.id) as debtors_count')
            ->groupBy('groups.name')
            ->orderByDesc('debtors_count')
            ->get();

        return view('management.reports.debtors', compact('debts', 'groups', 'debtorsByGroup'));
    }

    public function attendance(Request $request): View
    {
        $facultyId = $this->facultyId();
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $groupId = $request->get('group_id');

        $attendanceData = collect();

        if ($groupId && $semesterId) {
            $studentIds = Student::where('group_id', $groupId)
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
                ->active()->pluck('id');

            $attendanceStats = Attendance::whereIn('student_id', $studentIds)
                ->whereHas('subjectAssignment', fn ($q) => $q->where('semester_id', $semesterId))
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

        $groups = Group::whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('management.reports.attendance', compact('attendanceData', 'groups', 'semesters', 'semesterId', 'groupId'));
    }

    public function gpa(Request $request): View
    {
        $facultyId = $this->facultyId();
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);

        $gpaData = Student::where('status', 'active')
            ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->with(['user', 'group'])
            ->orderByDesc('cumulative_gpa')
            ->paginate(50)
            ->withQueryString();

        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('management.reports.gpa', compact('gpaData', 'semesters', 'semesterId'));
    }

    public function examResults(Request $request): View
    {
        $facultyId = $this->facultyId();
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $groupId = $request->get('group_id');
        $academicYearId = $request->get('academic_year_id');

        $query = Semester::with('academicYear')->orderByDesc('start_date');
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        $semesters = $query->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        $results = collect();

        if ($semesterId) {
            $query = SemesterGrade::where('semester_id', $semesterId)
                ->where('is_finalized', true)
                ->whereHas('subjectAssignment.group.specialty', fn ($q) => $q->where('faculty_id', $facultyId))
                ->with(['student.user', 'student.group', 'subjectAssignment.subject']);

            if ($groupId) {
                $query->whereHas('student', fn ($q) => $q->where('group_id', $groupId));
            }

            $results = $query->orderBy('student_id')->paginate(50)->withQueryString();
        }

        $groups = Group::whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();

        return view('management.reports.exam-results', compact('results', 'groups', 'semesters', 'semesterId', 'groupId', 'academicYears', 'academicYearId'));
    }

    public function export(string $type, Request $request)
    {
        $facultyId = $this->facultyId();
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $groupId = $request->get('group_id');

        $facultyScope = fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId));

        return match ($type) {
            'students' => $this->exportStudentsExcel($facultyId, $groupId),
            'debtors' => $this->exportDebtorsExcel($facultyId, $groupId),
            'attendance' => $this->exportAttendanceExcel($facultyId, $groupId, $semesterId),
            'gpa' => $this->exportGpaExcel($facultyId),
            'exam-results' => $this->exportExamResultsExcel($facultyId, $semesterId, $groupId),
            'debtors-pdf' => $this->exportDebtorsPdf($facultyId, $groupId),
            default => back()->with('info', 'Намуди содирот номаълум аст.'),
        };
    }

    private function exportStudentsExcel(?int $facultyId, ?int $groupId)
    {
        $query = Student::with(['user', 'group', 'specialty.faculty', 'course'])->active()
            ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId));
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        $students = $query->orderBy('id')->get();

        return Excel::download(new StudentsExport($students), 'students.xlsx');
    }

    private function exportDebtorsExcel(?int $facultyId, ?int $groupId)
    {
        $query = AcademicDebt::with(['student.user', 'student.group', 'subject', 'semester'])->open()
            ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId)));
        if ($groupId) {
            $query->whereHas('student', fn ($q) => $q->where('group_id', $groupId));
        }
        $debts = $query->orderByDesc('debt_date')->get();

        return Excel::download(new DebtorsExport($debts), 'debtors.xlsx');
    }

    private function exportAttendanceExcel(?int $facultyId, ?int $groupId, ?int $semesterId)
    {
        if (! $groupId || ! $semesterId) {
            return back()->with('info', 'Барои содирот гурӯҳ ва семестрро интихоб кунед.');
        }

        $studentIds = Student::where('group_id', $groupId)
            ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->pluck('id');
        $attendanceStats = Attendance::whereIn('student_id', $studentIds)
            ->whereHas('subjectAssignment', fn ($q) => $q->where('semester_id', $semesterId))
            ->selectRaw('student_id,
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("present", "late", "excused", "sick") THEN 1 ELSE 0 END) as present')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $students = Student::whereIn('id', $studentIds)->with('user')->get();
        $rows = $students->map(function ($student) use ($attendanceStats) {
            $stats = $attendanceStats->get($student->id);
            $total = $stats?->total ?? 0;
            $present = $stats?->present ?? 0;
            $absent = $total - $present;
            $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 100;

            return [
                'Донишҷӯ' => $student->user?->full_name ?? '-',
                'Гурӯҳ' => $student->group?->name ?? '-',
                'Ҳамагӣ' => $total,
                'Ҳозир' => $present,
                'Ғоиб' => $absent,
                'Фоиз (%)' => $percentage,
            ];
        })->values();

        return Excel::download(new AttendanceExport($rows), 'attendance.xlsx');
    }

    private function exportGpaExcel(?int $facultyId)
    {
        $gpaData = Student::where('status', 'active')
            ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->with(['user', 'group'])
            ->orderByDesc('cumulative_gpa')
            ->get();

        $rows = $gpaData->map(function ($student) {
            return [
                'Донишҷӯ' => $student->user?->full_name ?? '-',
                'Гурӯҳ' => $student->group?->name ?? '-',
                'GPA' => $student->cumulative_gpa ?? 0,
            ];
        })->values();

        return Excel::download(new GpaExport($rows), 'gpa.xlsx');
    }

    private function exportExamResultsExcel(?int $facultyId, ?int $semesterId, ?int $groupId)
    {
        $query = SemesterGrade::where('semester_id', $semesterId)
            ->where('is_finalized', true)
            ->whereHas('subjectAssignment.group.specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->with(['student.user', 'student.group', 'subjectAssignment.subject']);

        if ($groupId) {
            $query->whereHas('student', fn ($q) => $q->where('group_id', $groupId));
        }

        $results = $query->orderBy('student_id')->get();

        $rows = $results->map(function ($grade) {
            return [
                'Донишҷӯ' => $grade->student?->user?->full_name ?? '-',
                'Гурӯҳ' => $grade->student?->group?->name ?? '-',
                'Фан' => $grade->subjectAssignment?->subject?->name ?? '-',
                'Баҳо' => $grade->letter_grade ?? '-',
                'Балл' => $grade->total_score ?? 0,
            ];
        })->values();

        return Excel::download(new ExamResultsExport($rows), 'exam_results.xlsx');
    }

    private function exportDebtorsPdf(?int $facultyId, ?int $groupId)
    {
        $query = AcademicDebt::with(['student.user', 'student.group', 'subject', 'semester'])
            ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId)));
        if ($groupId) {
            $query->whereHas('student', fn ($q) => $q->where('group_id', $groupId));
        }
        $debts = $query->orderByDesc('debt_date')->get();

        $pdf = Pdf::loadView('management.reports.debtors-pdf', compact('debts'));
        $pdf->setPaper('a4');

        return $pdf->stream('debtors.pdf');
    }
}
