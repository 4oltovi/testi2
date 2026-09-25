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
            $studentQuery = Student::where('status', 'active')
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId));
            $teacherQuery = Teacher::where('status', 'active')
                ->whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId));
            $groupQuery = Group::where('is_active', true)
                ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId));
            $debtQuery = AcademicDebt::whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
                );

            $stats['total_students'] = $studentQuery->count();
            $stats['total_teachers'] = $teacherQuery->count();
            $stats['total_groups'] = $groupQuery->count();
            $stats['total_faculties'] = $facultyId ? 1 : 0;
            $stats['students_with_debts'] = $studentQuery->where('has_debts', true)->count();
            $stats['active_debts'] = $debtQuery->count();

            $stats['recent_students'] = $studentQuery
                ->with('user', 'group')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $stats['recent_debts'] = $debtQuery
                ->with('student.user')
                ->orderByDesc('debt_date')
                ->limit(5)
                ->get();

            $currentSemester = Semester::current();

            $stats['students_by_course'] = $studentQuery
                ->join('courses', 'students.course_id', '=', 'courses.id')
                ->selectRaw('courses.number as course, COUNT(*) as count')
                ->groupBy('courses.number')
                ->orderBy('courses.number')
                ->get();

            $stats['students_by_group'] = $studentQuery
                ->join('groups', 'students.group_id', '=', 'groups.id')
                ->selectRaw('groups.name as group_name, COUNT(*) as count')
                ->groupBy('groups.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $stats['students_by_specialty'] = $studentQuery
                ->join('specialties', 'students.specialty_id', '=', 'specialties.id')
                ->selectRaw('specialties.name as specialty, COUNT(*) as count')
                ->groupBy('specialties.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            if ($currentSemester) {
                $semesterGradeQuery = SemesterGrade::where('semester_id', $currentSemester->id)
                    ->where('is_finalized', true)
                    ->whereHas('subjectAssignment.group.specialty', fn ($q) => $q->where('faculty_id', $facultyId));

                $stats['semester_grades_summary'] = $semesterGradeQuery
                    ->selectRaw('letter_grade, COUNT(*) as count')
                    ->groupBy('letter_grade')
                    ->orderByDesc('count')
                    ->get();
            }

            $attendanceQuery = Attendance::whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
            );

            $stats['attendance_summary'] = $attendanceQuery
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            $stats['debts_by_status'] = $debtQuery
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();
        } catch (\Throwable $e) {
            $stats = array_map(fn ($v) => is_numeric($v) ? 0 : (is_countable($v) ? collect() : $v), $stats);
        }

        return view('management.dashboard.index', compact('stats', 'user', 'role', 'faculty'));
    }
}
