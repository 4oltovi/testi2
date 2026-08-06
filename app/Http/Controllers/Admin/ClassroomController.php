<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(Request $request): View
    {
        $query = Classroom::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('building', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($building = $request->get('building')) {
            $query->where('building', $building);
        }

        $classrooms = $query->orderBy('building')->orderBy('name')->paginate(25)->withQueryString();
        $buildings = Classroom::select('building')->distinct()->whereNotNull('building')->pluck('building');

        return view('admin.structure.classrooms.index', compact('classrooms', 'buildings'));
    }

    public function create(): View
    {
        return view('admin.structure.classrooms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'building' => 'nullable|string|max:50',
            'floor' => 'nullable|integer|min:0|max:10',
            'capacity' => 'required|integer|min:5|max:500',
            'type' => 'required|in:lecture,practice,lab,computer,gym,other',
            'has_projector' => 'boolean',
            'has_computers' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Рақами аудитория ҳатмӣ аст.',
            'capacity.required' => 'Ҷойгоҳ ҳатмӣ аст.',
            'type.required' => 'Навъ ҳатмӣ аст.',
        ]);

        $validated['has_projector'] = $request->boolean('has_projector');
        $validated['has_computers'] = $request->boolean('has_computers');
        $validated['is_active'] = $request->boolean('is_active', true);

        Classroom::create($validated);

        return redirect()->route('admin.structure.classrooms.index')
            ->with('success', 'Аудитория бомуваффақият сохта шуд.');
    }

    public function edit(Classroom $classroom): View
    {
        return view('admin.structure.classrooms.edit', compact('classroom'));
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'building' => 'nullable|string|max:50',
            'floor' => 'nullable|integer|min:0|max:10',
            'capacity' => 'required|integer|min:5|max:500',
            'type' => 'required|in:lecture,practice,lab,computer,gym,other',
            'has_projector' => 'boolean',
            'has_computers' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['has_projector'] = $request->boolean('has_projector');
        $validated['has_computers'] = $request->boolean('has_computers');
        $validated['is_active'] = $request->boolean('is_active', true);

        $classroom->update($validated);

        return redirect()->route('admin.structure.classrooms.index')
            ->with('success', 'Аудитория бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        if ($classroom->schedules()->exists()) {
            return back()->with('error', 'Аудиторияро нест кардан мумкин нест — дар ҷадвали дарсӣ истифода мешавад.');
        }

        $classroom->delete();

        return redirect()->route('admin.structure.classrooms.index')
            ->with('success', 'Аудитория нест карда шуд.');
    }
}
