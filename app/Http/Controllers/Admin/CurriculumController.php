<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Semester;
use App\Models\Specialty;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function index(Request $request): View
    {
        $query = Curriculum::with(['specialty.department', 'subject', 'course', 'semester.academicYear']);

        if ($specialtyId = $request->get('specialty_id')) {
            $query->where('specialty_id', $specialtyId);
        }

        if ($courseId = $request->get('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($semesterId = $request->get('semester_id')) {
            $query->where('semester_id', $semesterId);
        }

        $curriculum = $query->orderBy('specialty_id')
            ->orderBy('course_id')
            ->orderBy('semester_id')
            ->paginate(30)->withQueryString();

        $specialties = Specialty::active()->with('department')->get();
        $courses = Course::orderBy('number')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('admin.structure.curriculum.index', compact('curriculum', 'specialties', 'courses', 'semesters'));
    }

    public function create(): View
    {
        $specialties = Specialty::active()->with('department.faculty')->get();
        $subjects = Subject::active()->orderBy('name')->get();
        $courses = Course::orderBy('number')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('admin.structure.curriculum.create', compact('specialties', 'subjects', 'courses', 'semesters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'subject_id' => 'required|exists:subjects,id',
            'course_id' => 'required|exists:courses,id',
            'semester_id' => 'required|exists:semesters,id',
            'credits' => 'required|integer|min:1|max:30',
            'total_hours' => 'required|integer|min:10|max:500',
            'lecture_hours' => 'nullable|integer|min:0',
            'practice_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
            'independent_hours' => 'nullable|integer|min:0',
            'exam_type' => 'required|in:exam,credit,diff_credit',
            'control_type' => 'required|in:rating_exam,rating_only,project,coursework',
            'is_elective' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'specialty_id.required' => 'Ихтисос ҳатмӣ аст.',
            'subject_id.required' => 'Фан ҳатмӣ аст.',
            'course_id.required' => 'Курс ҳатмӣ аст.',
            'semester_id.required' => 'Семестр ҳатмӣ аст.',
            'credits.required' => 'Кредитҳо ҳатмӣ аст.',
        ]);

        // Санҷиши дубликат
        $exists = Curriculum::where('specialty_id', $validated['specialty_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('semester_id', $validated['semester_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->with('error', 'Ин фан барои ин ихтисос/семестр аллакай дар нақша мавҷуд аст.');
        }

        $validated['is_elective'] = $request->boolean('is_elective');
        $validated['is_active'] = $request->boolean('is_active', true);

        Curriculum::create($validated);

        return redirect()->route('admin.structure.curriculum.index')
            ->with('success', 'Нақшаи таълимӣ бомуваффақият илова шуд.');
    }

    public function edit(Curriculum $curriculum): View
    {
        $specialties = Specialty::active()->with('department.faculty')->get();
        $subjects = Subject::active()->orderBy('name')->get();
        $courses = Course::orderBy('number')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('admin.structure.curriculum.edit', compact('curriculum', 'specialties', 'subjects', 'courses', 'semesters'));
    }

    public function update(Request $request, Curriculum $curriculum): RedirectResponse
    {
        $validated = $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'subject_id' => 'required|exists:subjects,id',
            'course_id' => 'required|exists:courses,id',
            'semester_id' => 'required|exists:semesters,id',
            'credits' => 'required|integer|min:1|max:30',
            'total_hours' => 'required|integer|min:10|max:500',
            'lecture_hours' => 'nullable|integer|min:0',
            'practice_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
            'independent_hours' => 'nullable|integer|min:0',
            'exam_type' => 'required|in:exam,credit,diff_credit',
            'control_type' => 'required|in:rating_exam,rating_only,project,coursework',
            'is_elective' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['is_elective'] = $request->boolean('is_elective');
        $validated['is_active'] = $request->boolean('is_active', true);

        $curriculum->update($validated);

        return redirect()->route('admin.structure.curriculum.index')
            ->with('success', 'Нақшаи таълимӣ бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Curriculum $curriculum): RedirectResponse
    {
        if ($curriculum->subjectAssignments()->exists()) {
            return back()->with('error', 'Нест кардан мумкин нест — таъиноти мавҷуд аст.');
        }

        $curriculum->delete();

        return redirect()->route('admin.structure.curriculum.index')
            ->with('success', 'Аз нақша хориҷ карда шуд.');
    }
}
