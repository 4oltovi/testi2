<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::with('semesters')
            ->orderByDesc('start_date')
            ->paginate(10);

        return view('admin.structure.academic-years.index', compact('academicYears'));
    }

    public function create(): View
    {
        return view('admin.structure.academic-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'status' => 'required|in:planning,active,completed',
            // Семестр 1
            'sem1_start' => 'required|date',
            'sem1_end' => 'required|date|after:sem1_start',
            'sem1_exam_start' => 'nullable|date',
            'sem1_exam_end' => 'nullable|date',
            // Семестр 2
            'sem2_start' => 'required|date',
            'sem2_end' => 'required|date|after:sem2_start',
            'sem2_exam_start' => 'nullable|date',
            'sem2_exam_end' => 'nullable|date',
        ], [
            'name.required' => 'Номи соли таҳсилӣ ҳатмӣ аст (мисол: 2024-2025).',
            'name.unique' => 'Ин соли таҳсилӣ аллакай мавҷуд аст.',
            'start_date.required' => 'Санаи оғоз ҳатмӣ аст.',
            'end_date.required' => 'Санаи анҷом ҳатмӣ аст.',
        ]);

        // Агар is_current бошад — дигаронро false кун
        if ($request->boolean('is_current')) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
        }

        $year = AcademicYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => $request->boolean('is_current'),
            'status' => $validated['status'],
        ]);

        // Сохтани семестрҳо
        $year->semesters()->create([
            'number' => 1,
            'name' => 'Семестри 1',
            'start_date' => $validated['sem1_start'],
            'end_date' => $validated['sem1_end'],
            'exam_start_date' => $validated['sem1_exam_start'] ?? null,
            'exam_end_date' => $validated['sem1_exam_end'] ?? null,
            'is_current' => false,
            'status' => 'planning',
        ]);

        $year->semesters()->create([
            'number' => 2,
            'name' => 'Семестри 2',
            'start_date' => $validated['sem2_start'],
            'end_date' => $validated['sem2_end'],
            'exam_start_date' => $validated['sem2_exam_start'] ?? null,
            'exam_end_date' => $validated['sem2_exam_end'] ?? null,
            'is_current' => false,
            'status' => 'planning',
        ]);

        return redirect()->route('admin.structure.academic-years.index')
            ->with('success', "Соли таҳсилии «{$year->name}» бо 2 семестр сохта шуд.");
    }

    public function show(AcademicYear $academicYear): View
    {
        $academicYear->load(['semesters', 'groups.specialty']);
        return view('admin.structure.academic-years.show', compact('academicYear'));
    }

    public function edit(AcademicYear $academicYear): View
    {
        $academicYear->load('semesters');
        return view('admin.structure.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validate([
            'name' => "required|string|max:20|unique:academic_years,name,{$academicYear->id}",
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'status' => 'required|in:planning,active,completed',
        ]);

        if ($request->boolean('is_current')) {
            AcademicYear::where('id', '!=', $academicYear->id)->where('is_current', true)->update(['is_current' => false]);
        }

        $academicYear->update([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => $request->boolean('is_current'),
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.structure.academic-years.index')
            ->with('success', "Соли таҳсилии «{$academicYear->name}» навсозӣ шуд.");
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->is_current) {
            return back()->with('error', 'Соли таҳсилии ҷориро нест кардан мумкин нест.');
        }

        if ($academicYear->groups()->exists()) {
            return back()->with('error', 'Нест кардан мумкин нест — гурӯҳҳо мавҷуданд.');
        }

        $academicYear->semesters()->delete();
        $academicYear->delete();

        return redirect()->route('admin.structure.academic-years.index')
            ->with('success', 'Соли таҳсилӣ нест карда шуд.');
    }
}
