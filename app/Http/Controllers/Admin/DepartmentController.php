<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Department::with(['faculty', 'head'])
            ->withCount(['specialties', 'teachers', 'subjects']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($facultyId = $request->get('faculty_id')) {
            $query->where('faculty_id', $facultyId);
        }

        $departments = $query->orderBy('sort_order')->paginate(20)->withQueryString();
        $faculties = Faculty::active()->orderBy('sort_order')->get();

        return view('admin.structure.departments.index', compact('departments', 'faculties'));
    }

    public function create(): View
    {
        $faculties = Faculty::active()->orderBy('sort_order')->get();
        $heads = User::whereHas('roles', fn($q) => $q->where('name', 'department_head'))
            ->where('status', 'active')->get();

        return view('admin.structure.departments.create', compact('faculties', 'heads'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:20',
            'code' => 'required|string|max:10|unique:departments,code',
            'head_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'faculty_id.required' => 'Факултет ҳатмӣ аст.',
            'name.required' => 'Номи кафедра ҳатмӣ аст.',
            'code.required' => 'Рамз ҳатмӣ аст.',
            'code.unique' => 'Ин рамз аллакай мавҷуд аст.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Department::create($validated);

        return redirect()->route('admin.structure.departments.index')
            ->with('success', 'Кафедра бомуваффақият сохта шуд.');
    }

    public function show(Department $department): View
    {
        $department->load(['faculty', 'head', 'specialties', 'teachers.user', 'subjects']);
        return view('admin.structure.departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        $faculties = Faculty::active()->orderBy('sort_order')->get();
        $heads = User::whereHas('roles', fn($q) => $q->whereIn('name', ['department_head', 'teacher']))
            ->where('status', 'active')->get();

        return view('admin.structure.departments.edit', compact('department', 'faculties', 'heads'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:20',
            'code' => "required|string|max:10|unique:departments,code,{$department->id}",
            'head_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $department->update($validated);

        return redirect()->route('admin.structure.departments.index')
            ->with('success', 'Кафедра бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->specialties()->exists()) {
            return back()->with('error', 'Кафедраро нест кардан мумкин нест — ихтисосҳо мавҷуданд.');
        }

        $department->delete();

        return redirect()->route('admin.structure.departments.index')
            ->with('success', 'Кафедра нест карда шуд.');
    }
}
