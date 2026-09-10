<?php

namespace App\Http\Controllers\Management;

use App\Enums\GradeScale;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\CurrentGrade;
use App\Models\Group;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\SubjectAssignment;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    use ResolvesDeanFaculty;

    public function assignments(Request $request): View
    {
        $facultyId = $this->facultyId();
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);

        $query = SubjectAssignment::with(['subject', 'teacher', 'group.specialty.department.faculty', 'semester'])
            ->where('is_active', true)
            ->whereHas('group.specialty.department', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($teacherId = $request->get('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }

        $assignments = $query->orderBy('group_id')->paginate(30)->withQueryString();

        $currentYear = AcademicYear::where('is_current', true)->first();
        $semesters = Semester::with('academicYear')
            ->when($currentYear, fn ($q) => $q->where('academic_year_id', $currentYear->id))
            ->orderBy('number')
            ->get();
        $groups = Group::whereHas('specialty.department', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();

        return view('management.journal.index', compact('assignments', 'semesters', 'groups', 'currentSemester', 'semesterId'));
    }

    public function grades(SubjectAssignment $subjectAssignment): View
    {
        $facultyId = $this->facultyId();

        if ($subjectAssignment->group?->specialty?->department?->faculty?->id !== $facultyId) {
            abort(403, 'Шумо ба ин саҳифа дастрасӣ надоред.');
        }

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'teacher', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');
        $semester = $subjectAssignment->semester;

        $grades = CurrentGrade::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->orderBy('grade_date')
            ->get()
            ->groupBy('student_id');

        return view('management.journal.grades', compact('subjectAssignment', 'students', 'semester', 'grades'));
    }

    public function semesterGrades(SubjectAssignment $subjectAssignment): View
    {
        $facultyId = $this->facultyId();

        if ($subjectAssignment->group?->specialty?->department?->faculty?->id !== $facultyId) {
            abort(403, 'Шумо ба ин саҳифа дастрасӣ надоред.');
        }

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'teacher', 'semester']);
        $students = $subjectAssignment->group->activeStudents
            ->sortBy(fn ($s) => ($s->user?->last_name ?? '').($s->user?->first_name ?? ''))
            ->values();
        $semester = $subjectAssignment->semester;
        $subject = $subjectAssignment->subject;

        $semesterGrades = SemesterGrade::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->get()
            ->keyBy('student_id');

        $grades = GradeScale::cases();

        return view('management.journal.semester-grades', compact(
            'subjectAssignment', 'students', 'semester', 'subject', 'semesterGrades', 'grades'
        ));
    }

    public function attendance(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $facultyId = $this->facultyId();

        if ($subjectAssignment->group?->specialty?->department?->faculty?->id !== $facultyId) {
            abort(403, 'Шумо ба ин саҳифа дастрасӣ надоред.');
        }

        $currentSemester = Semester::current();
        $date = $request->get('date', now()->format('Y-m-d'));
        $lessonNumber = (int) ($request->get('lesson_number', 1));

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents ?? collect();
        $semester = $subjectAssignment->semester;

        $existingAttendance = Attendance::where('subject_assignment_id', $subjectAssignment->id)
            ->where('lesson_date', $date)
            ->where('lesson_number', $lessonNumber)
            ->pluck('status', 'student_id');

        $attendanceStats = Attendance::where('subject_assignment_id', $subjectAssignment->id)
            ->selectRaw('student_id, COUNT(*) as total, SUM(CASE WHEN status IN ("present","late","excused","sick") THEN 1 ELSE 0 END) as present_count, SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        return view('management.journal.attendance', compact(
            'subjectAssignment', 'students', 'semester', 'date', 'lessonNumber',
            'existingAttendance', 'attendanceStats'
        ));
    }
}
