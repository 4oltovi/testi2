<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Group;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(Request $request): View
    {
        $query = Group::with(['specialty.department.faculty', 'course', 'academicYear', 'curator'])
            ->withCount(['activeStudents']);

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
        $specialties = Specialty::active()->with('department.faculty')->get();
        $courses = Course::orderBy('number')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('admin.structure.groups.index', compact('groups', 'specialties', 'courses', 'academicYears'));
    }

    public function create(): View
    {
        $specialties = Specialty::active()->with('department.faculty')->get();
        $courses = Course::orderBy('number')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $curators = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->where('status', 'active')->get();

        return view('admin.structure.groups.create', compact('specialties', 'courses', 'academicYears', 'curators'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'course_id' => 'required|exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:30',
            'code' => 'required|string|max:20|unique:groups,code',
            'curator_id' => 'nullable|exists:users,id',
            'max_students' => 'nullable|integer|min:5|max:50',
            'is_active' => 'boolean',
        ], [
            'specialty_id.required' => 'Ихтисос ҳатмӣ аст.',
            'course_id.required' => 'Курс ҳатмӣ аст.',
            'academic_year_id.required' => 'Соли таҳсилӣ ҳатмӣ аст.',
            'name.required' => 'Номи гурӯҳ ҳатмӣ аст.',
            'code.required' => 'Рамзи гурӯҳ ҳатмӣ аст.',
            'code.unique' => 'Ин рамз аллакай мавҷуд аст.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['max_students'] = $validated['max_students'] ?? 25;

        Group::create($validated);

        return redirect()->route('admin.structure.groups.index')
            ->with('success', 'Гурӯҳ бомуваффақият сохта шуд.');
    }

    public function show(Group $group): View
    {
        $group->load([
            'specialty.department.faculty',
            'course',
            'academicYear',
            'curator',
            'activeStudents.user',
            'subjectAssignments.curriculum.subject',
            'subjectAssignments.teacher',
        ]);

        return view('admin.structure.groups.show', compact('group'));
    }

    public function edit(Group $group): View
    {
        $specialties = Specialty::active()->with('department.faculty')->get();
        $courses = Course::orderBy('number')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $curators = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->where('status', 'active')->get();

        return view('admin.structure.groups.edit', compact('group', 'specialties', 'courses', 'academicYears', 'curators'));
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'course_id' => 'required|exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:30',
            'code' => "required|string|max:20|unique:groups,code,{$group->id}",
            'curator_id' => 'nullable|exists:users,id',
            'max_students' => 'nullable|integer|min:5|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $group->update($validated);

        return redirect()->route('admin.structure.groups.index')
            ->with('success', 'Гурӯҳ бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        if ($group->students()->exists()) {
            return back()->with('error', 'Гурӯҳро нест кардан мумкин нест — донишҷӯён мавҷуданд.');
        }

        $group->delete();

        return redirect()->route('admin.structure.groups.index')
            ->with('success', 'Гурӯҳ нест карда шуд.');
    }
}
