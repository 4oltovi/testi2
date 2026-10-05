<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\Teacher;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        $role = $user->getPrimaryRoleAttribute();
        $faculty = $user->deanFaculty;
        $facultyId = $faculty?->id;

        $stats = [
            'total_students' => 0,
            'total_teachers' => 0,
            'total_groups' => 0,
            'total_faculties' => 0,
            'students_with_debts' => 0,
            'active_debts' => 0,
            'recent_students' => collect(),
            'recent_debts' => collect(),
            'students_by_course' => collect(),
            'students_by_group' => collect(),
            'students_by_specialty' => collect(),
            'semester_grades_summary' => collect(),
            'attendance_summary' => collect(),
            'debts_by_status' => collect(),
            'faculty_name' => $faculty?->name ?? '',
        ];

        try {
            $baseStudentQuery = fn () => Student::where('status', 'active')
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId));
            $baseTeacherQuery = fn () => Teacher::where('status', 'active')
                ->whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId));
            $baseGroupQuery = fn () => Group::where('is_active', true)
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId));
            $baseDebtQuery = fn () => AcademicDebt::whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId)));

            $stats['total_students'] = $baseStudentQuery()->count();
            $stats['total_teachers'] = $baseTeacherQuery()->count();
            $stats['total_groups'] = $baseGroupQuery()->count();
            $stats['total_faculties'] = $facultyId ? 1 : 0;
            $stats['students_with_debts'] = $baseStudentQuery()->where('has_debts', true)->count();
            $stats['active_debts'] = $baseDebtQuery()->count();

            $stats['recent_students'] = $baseStudentQuery()
                ->with('user', 'group')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $stats['recent_debts'] = $baseDebtQuery()
                ->with('student.user')
                ->orderByDesc('debt_date')
                ->limit(5)
                ->get();

            $currentSemester = Semester::current();

            $stats['students_by_course'] = $baseStudentQuery()
                ->join('courses', 'students.course_id', '=', 'courses.id')
                ->selectRaw('courses.number as course, COUNT(*) as count')
                ->groupBy('courses.number')
                ->orderBy('courses.number')
                ->get();

            $stats['students_by_group'] = $baseStudentQuery()
                ->join('groups', 'students.group_id', '=', 'groups.id')
                ->selectRaw('groups.name as group_name, COUNT(*) as count')
                ->groupBy('groups.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $stats['students_by_specialty'] = $baseStudentQuery()
                ->join('specialties', 'students.specialty_id', '=', 'specialties.id')
                ->selectRaw('specialties.name as specialty, COUNT(*) as count')
                ->groupBy('specialties.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            if ($currentSemester) {
                $stats['semester_grades_summary'] = SemesterGrade::where('semester_id', $currentSemester->id)
                    ->where('is_finalized', true)
                    ->whereHas('subjectAssignment.group.specialty', fn ($q) => $q->where('faculty_id', $facultyId))
                    ->selectRaw('letter_grade, COUNT(*) as count')
                    ->groupBy('letter_grade')
                    ->orderByDesc('count')
                    ->get();
            }

            $stats['attendance_summary'] = Attendance::whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId)))
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            $stats['debts_by_status'] = $baseDebtQuery()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();
        } catch (\Throwable $e) {
            \Log::error('Management dashboard error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $stats = array_map(fn ($v) => is_numeric($v) ? 0 : (is_countable($v) ? collect() : $v), $stats);
        }

        return view('management.dashboard.index', compact('stats', 'user', 'role', 'faculty'));
    }
}
