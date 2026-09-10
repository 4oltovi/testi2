<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Subject;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = Subject::with(['department.faculty'])
            ->whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $subjects = $query->orderBy('name')->paginate(25)->withQueryString();
        $departments = Department::where('faculty_id', $facultyId)->active()->orderBy('name')->get();

        return view('management.subjects.index', compact('subjects', 'departments'));
    }

    public function show(Subject $subject): View
    {
        $this->abortIfFacultyMismatch($subject);

        $subject->load([
            'department.faculty',
            'subjectAssignments.group',
            'subjectAssignments.semester',
            'questionBanks',
        ]);

        return view('management.subjects.show', compact('subject'));
    }
}
