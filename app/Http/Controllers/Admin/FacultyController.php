<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacultyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Faculty::with(['institution', 'dean', 'departments'])
            ->withCount(['departments']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $faculties = $query->orderBy('sort_order')->paginate(20)->withQueryString();

        return view('admin.structure.faculties.index', compact('faculties'));
    }

    public function create(): View
    {
        $institutions = Institution::all();
        $deans = User::whereHas('roles', fn($q) => $q->where('name', 'dean'))
            ->where('status', 'active')->get();

        return view('admin.structure.faculties.create', compact('institutions', 'deans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:20',
            'code' => 'required|string|max:10|unique:faculties,code',
            'institution_id' => 'required|exists:institutions,id',
            'dean_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'Номи факултет ҳатмӣ аст.',
            'code.required' => 'Рамзи факултет ҳатмӣ аст.',
            'code.unique' => 'Ин рамз аллакай мавҷуд аст.',
            'institution_id.required' => 'Муассиса ҳатмӣ аст.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Faculty::create($validated);

        return redirect()->route('admin.structure.faculties.index')
            ->with('success', 'Факултет бомуваффақият сохта шуд.');
    }

    public function show(Faculty $faculty): View
    {
        $faculty->load(['institution', 'dean', 'departments.head', 'departments.specialties']);
        return view('admin.structure.faculties.show', compact('faculty'));
    }

    public function edit(Faculty $faculty): View
    {
        $institutions = Institution::all();
        $deans = User::whereHas('roles', fn($q) => $q->whereIn('name', ['dean', 'vice_dean']))
            ->where('status', 'active')->get();

        return view('admin.structure.faculties.edit', compact('faculty', 'institutions', 'deans'));
    }

    public function update(Request $request, Faculty $faculty): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:20',
            'code' => "required|string|max:10|unique:faculties,code,{$faculty->id}",
            'institution_id' => 'required|exists:institutions,id',
            'dean_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $faculty->update($validated);

        return redirect()->route('admin.structure.faculties.index')
            ->with('success', 'Факултет бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        if ($faculty->departments()->exists()) {
            return back()->with('error', 'Факултетро нест кардан мумкин нест — кафедраҳо мавҷуданд.');
        }

        $faculty->delete();

        return redirect()->route('admin.structure.faculties.index')
            ->with('success', 'Факултет нест карда шуд.');
    }
}
