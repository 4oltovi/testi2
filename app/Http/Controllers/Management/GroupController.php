<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Group;
use App\Models\Specialty;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = Group::with(['specialty.faculty', 'course', 'academicYear', 'curator'])
            ->withCount(['activeStudents'])
            ->whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($specialtyId = $request->get('specialty_id')) {
            $query->where('specialty_id', $specialtyId);
        }
        if ($courseId = $request->get('course_id')) {
            $query->where('course_id', $courseId);
        }
        if ($yearId = $request->get('academic_year_id')) {
            $query->where('academic_year_id', $yearId);
        }

        $groups = $query->orderBy('name')->paginate(25)->withQueryString();
        $specialties = Specialty::whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();
        $courses = Course::orderBy('number')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('management.groups.index', compact('groups', 'specialties', 'courses', 'academicYears'));
    }

    public function show(Group $group): View
    {
        $this->abortIfFacultyMismatch($group);

        $group->load([
            'specialty.faculty',
            'course',
            'academicYear',
            'curator',
            'activeStudents.user',
            'subjectAssignments.subject',
            'subjectAssignments.teacher',
        ]);

        return view('management.groups.show', compact('group'));
    }
}
