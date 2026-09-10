<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialtyController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = Specialty::with(['department.faculty'])
            ->withCount(['groups', 'students'])
            ->whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $specialties = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('management.specialties.index', compact('specialties'));
    }

    public function show(Specialty $specialty): View
    {
        $this->abortIfFacultyMismatch($specialty);

        $specialty->load([
            'department.faculty',
            'groups.course',
            'groups.academicYear',
            'subjectAssignments.subject',
            'subjectAssignments.semester',
        ]);

        return view('management.specialties.show', compact('specialty'));
    }
}
