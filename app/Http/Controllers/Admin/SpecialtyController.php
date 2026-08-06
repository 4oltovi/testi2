<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialtyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Specialty::with(['department.faculty'])
            ->withCount(['groups', 'students']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->get('department_id')) {
            $query->where('department_id', $departmentId);
        }

        $specialties = $query->orderBy('name')->paginate(20)->withQueryString();
        $departments = Department::active()->with('faculty')->orderBy('name')->get();

        return view('admin.structure.specialties.index', compact('specialties', 'departments'));
    }

    public function create(): View
    {
        $departments = Department::active()->with('faculty')->orderBy('name')->get();
        return view('admin.structure.specialties.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:specialties,code',
            'education_level' => 'required|in:bachelor,master,specialist',
            'study_years' => 'required|integer|min:1|max:7',
            'total_credits' => 'required|integer|min:60|max:500',
            'study_form' => 'required|in:full_time,part_time,evening',
            'is_active' => 'boolean',
        ], [
            'department_id.required' => 'Кафедра ҳатмӣ аст.',
            'name.required' => 'Номи ихтисос ҳатмӣ аст.',
            'code.required' => 'Рамзи ихтисос ҳатмӣ аст.',
            'code.unique' => 'Ин рамз аллакай мавҷуд аст.',
            'study_years.required' => 'Муддати таҳсил ҳатмӣ аст.',
            'total_credits.required' => 'Маҷмӯи кредитҳо ҳатмӣ аст.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Specialty::create($validated);

        return redirect()->route('admin.structure.specialties.index')
            ->with('success', 'Ихтисос бомуваффақият сохта шуд.');
    }

    public function show(Specialty $specialty): View
    {
        $specialty->load(['department.faculty', 'groups.course', 'curriculum.subject', 'curriculum.semester']);
        return view('admin.structure.specialties.show', compact('specialty'));
    }

    public function edit(Specialty $specialty): View
    {
        $departments = Department::active()->with('faculty')->orderBy('name')->get();
        return view('admin.structure.specialties.edit', compact('specialty', 'departments'));
    }

    public function update(Request $request, Specialty $specialty): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => "required|string|max:20|unique:specialties,code,{$specialty->id}",
            'education_level' => 'required|in:bachelor,master,specialist',
            'study_years' => 'required|integer|min:1|max:7',
            'total_credits' => 'required|integer|min:60|max:500',
            'study_form' => 'required|in:full_time,part_time,evening',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $specialty->update($validated);

        return redirect()->route('admin.structure.specialties.index')
            ->with('success', 'Ихтисос бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Specialty $specialty): RedirectResponse
    {
        if ($specialty->groups()->exists() || $specialty->students()->exists()) {
            return back()->with('error', 'Ихтисосро нест кардан мумкин нест — гурӯҳҳо ё донишҷӯён мавҷуданд.');
        }

        $specialty->delete();

        return redirect()->route('admin.structure.specialties.index')
            ->with('success', 'Ихтисос нест карда шуд.');
    }
}
