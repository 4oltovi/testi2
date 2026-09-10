<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Semester;
use App\Models\Teacher;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = Teacher::with(['user', 'department.faculty'])
            ->whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($position = $request->get('position')) {
            $query->where('position', 'like', "%{$position}%");
        }

        $teachers = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();
        $departments = Department::where('faculty_id', $facultyId)->active()->orderBy('name')->get();

        return view('management.teachers.index', compact('teachers', 'departments'));
    }

    public function show(Teacher $teacher): View
    {
        $this->abortIfFacultyMismatch($teacher);

        $teacher->load([
            'user.roles',
            'department.faculty',
            'subjectAssignments.subject',
            'subjectAssignments.group',
            'subjectAssignments.semester',
            'activityLog',
        ]);

        $currentSemester = Semester::current();
        $currentAssignments = $teacher->subjectAssignments()
            ->when($currentSemester, fn ($q) => $q->where('semester_id', $currentSemester->id))
            ->where('is_active', true)
            ->with(['subject', 'group'])
            ->get();

        return view('management.teachers.show', compact('teacher', 'currentAssignments', 'currentSemester'));
    }
}
