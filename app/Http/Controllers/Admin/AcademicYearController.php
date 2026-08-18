<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::with(['semesters' => fn($q) => $q->orderBy('number')])
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

        $year = AcademicYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => false,
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

        // АГАР "Ҷорӣ" интихоб шуда бошад — ҳам сол ва ҳам семестр ҳамоҳанг мешаванд
        if ($request->boolean('is_current')) {
            $this->makeYearCurrent($year);
        }

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

        $academicYear->update([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
        ]);

        if ($request->boolean('is_current')) {
            // Ҷорӣ шуд — семестрҳо ҳам ҳамоҳанг мешаванд
            $this->makeYearCurrent($academicYear);
        } else {
            $academicYear->update(['is_current' => false]);
        }

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

    // ================================================================
    // НАВ: Фаъол кардани СОЛ (бо ҳамоҳангсозии семестрҳо)
    // ================================================================
    public function activate(AcademicYear $academicYear): RedirectResponse
    {
        DB::transaction(function () use ($academicYear) {
            $this->makeYearCurrent($academicYear);
        });

        return back()->with('success', "Соли «{$academicYear->name}» фаъол шуд.");
    }

    // ================================================================
    // НАВ: Фаъол кардани СЕМЕСТРИ мушаххас
    // ================================================================
    public function activateSemester(Semester $semester): RedirectResponse
    {
        DB::transaction(function () use ($semester) {
            // 1) Ҳамаи семестрҳо: ғайриҷорӣ
            Semester::query()->update(['is_current' => false]);

            // 2) Семестри интихобшуда: ҷорӣ ва фаъол
            $semester->update(['is_current' => true, 'status' => 'active']);

            // 3) Семестрҳои дигари ҳамон сол, ки фаъол буданд: анҷомёфта
            Semester::whereKeyNot($semester->id)
                ->where('academic_year_id', $semester->academic_year_id)
                ->where('status', 'active')
                ->update(['status' => 'completed']);

            // 4) Соли ин семестр: ҷорӣ; солҳои дигари фаъол: анҷомёфта
            $year = $semester->academicYear;

            if ($year) {
                AcademicYear::whereKeyNot($year->id)->update(['is_current' => false]);
                AcademicYear::whereKeyNot($year->id)
                    ->where('status', 'active')
                    ->update(['status' => 'completed']);

                $year->update(['is_current' => true, 'status' => 'active']);
            }
        });

        return back()->with('success', "«{$semester->name}» фаъол шуд.");
    }

    // ================================================================
    // Ёрдамчӣ: солро ҷорӣ кунад ва семестри дурустро интихоб кунад
    // ================================================================
    private function makeYearCurrent(AcademicYear $academicYear): void
    {
        // Солҳои дигар: ғайриҷорӣ (солҳои planning ҳамон planning мемонанд)
        AcademicYear::whereKeyNot($academicYear->id)->update(['is_current' => false]);
        AcademicYear::whereKeyNot($academicYear->id)
            ->where('status', 'active')
            ->update(['status' => 'completed']);

        // Соли интихобшуда: ҷорӣ ва фаъол
        $academicYear->update(['is_current' => true, 'status' => 'active']);

        // Ҳамаи семестрҳо: ғайриҷорӣ
        Semester::query()->update(['is_current' => false]);

        // Семестри ҷорӣ: агар имрӯз дар байни санаҳо бошад — ҳамон,
        // вагарна семестри аввал
        $semesters = $academicYear->semesters()->orderBy('number')->get();

        $current = $semesters->first(
            fn($s) => $s->start_date && $s->end_date
                && now()->between($s->start_date, $s->end_date)
        ) ?? $semesters->first();

        $current?->update(['is_current' => true, 'status' => 'active']);
    }
}
