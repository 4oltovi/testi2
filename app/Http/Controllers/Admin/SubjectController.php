<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Subject::with(['department.faculty']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->get('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($examType = $request->get('exam_type')) {
            $query->where('exam_type', $examType);
        }

        $subjects = $query->orderBy('name')->paginate(25)->withQueryString();
        $departments = Department::active()->with('faculty')->orderBy('name')->get();

        return view('admin.structure.subjects.index', compact('subjects', 'departments'));
    }

    public function create(): View
    {
        $departments = Department::active()->with('faculty')->orderBy('name')->get();
        return view('admin.structure.subjects.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:30',
            'code' => 'required|string|max:20|unique:subjects,code',
            'credits' => 'required|integer|min:1|max:30',
            'total_hours' => 'required|integer|min:10|max:500',
            'lecture_hours' => 'nullable|integer|min:0',
            'practice_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
            'independent_hours' => 'nullable|integer|min:0',
            'exam_type' => 'required|in:exam,credit,diff_credit',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ], [
            'department_id.required' => 'Кафедра ҳатмӣ аст.',
            'name.required' => 'Номи фан ҳатмӣ аст.',
            'code.required' => 'Рамзи фан ҳатмӣ аст.',
            'code.unique' => 'Ин рамз аллакай мавҷуд аст.',
            'credits.required' => 'Шумораи кредитҳо ҳатмӣ аст.',
            'total_hours.required' => 'Соатҳои умумӣ ҳатмӣ аст.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Subject::create($validated);

        return redirect()->route('admin.structure.subjects.index')
            ->with('success', 'Фан бомуваффақият сохта шуд.');
    }

    public function show(Subject $subject): View
    {
        $subject->load(['department.faculty', 'curriculum.specialty', 'curriculum.semester', 'questionBanks']);
        return view('admin.structure.subjects.show', compact('subject'));
    }

    public function edit(Subject $subject): View
    {
        $departments = Department::active()->with('faculty')->orderBy('name')->get();
        return view('admin.structure.subjects.edit', compact('subject', 'departments'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:30',
            'code' => "required|string|max:20|unique:subjects,code,{$subject->id}",
            'credits' => 'required|integer|min:1|max:30',
            'total_hours' => 'required|integer|min:10|max:500',
            'lecture_hours' => 'nullable|integer|min:0',
            'practice_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
            'independent_hours' => 'nullable|integer|min:0',
            'exam_type' => 'required|in:exam,credit,diff_credit',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $subject->update($validated);

        return redirect()->route('admin.structure.subjects.index')
            ->with('success', 'Фан бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->curriculum()->exists()) {
            return back()->with('error', 'Фанро нест кардан мумкин нест — дар нақшаи таълимӣ мавҷуд аст.');
        }

        $subject->delete();

        return redirect()->route('admin.structure.subjects.index')
            ->with('success', 'Фан нест карда шуд.');
    }
}
