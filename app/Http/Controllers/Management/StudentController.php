<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Group;
use App\Models\Specialty;
use App\Models\Student;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = Student::with(['user', 'group', 'specialty.department.faculty', 'course'])
            ->whereHas('specialty.department', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('student_id_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('login', 'like', "%{$search}%");
                    });
            });
        }

        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($specialtyId = $request->get('specialty_id')) {
            $query->where('specialty_id', $specialtyId);
        }
        if ($courseId = $request->get('course_id')) {
            $query->where('course_id', $courseId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($request->get('has_debts') === '1') {
            $query->where('has_debts', true);
        }
        if ($educationForm = $request->get('education_form')) {
            $query->where('education_form', $educationForm);
        }

        $students = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();
        $groups = Group::whereHas('specialty.department', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();
        $specialties = Specialty::whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->get();
        $courses = Course::orderBy('number')->get();

        return view('management.students.index', compact('students', 'groups', 'specialties', 'courses'));
    }

    public function search(Request $request)
    {
        $facultyId = $this->facultyId();
        $search = $request->get('search', '');

        $query = Student::with(['user', 'group'])
            ->whereHas('specialty.department', fn ($q) => $q->where('faculty_id', $facultyId))
            ->limit(20);

        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('student_id_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('login', 'like', "%{$search}%");
                    });
            });
        }

        $students = $query->get();

        return response()->json(
            $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->user?->full_name ?? '',
                'student_id' => $s->student_id_number,
                'group' => $s->group?->name,
                'url' => route('management.students.show', $s),
            ])
        );
    }

    public function show(Student $student): View
    {
        $this->abortIfFacultyMismatch($student);

        $student->load([
            'user.roles',
            'group',
            'specialty.department.faculty',
            'course',
            'semesterGrades.subjectAssignment.subject',
            'semesterGpas.semester',
            'statusHistory',
            'activeDebts.subject',
        ]);

        return view('management.students.show', compact('student'));
    }
}
