<?php

namespace App\Http\Controllers\Management;

use App\Enums\DebtStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\Group;
use App\Models\Semester;
use App\Traits\ResolvesDeanFaculty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DebtController extends Controller
{
    use ResolvesDeanFaculty;

    public function index(Request $request): View
    {
        $facultyId = $this->facultyId();

        $query = AcademicDebt::with(['student.user', 'student.group', 'subject', 'semester'])
            ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
            );

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($groupId = $request->get('group_id')) {
            $query->whereHas('student', fn ($q) => $q->where('group_id', $groupId));
        }
        if ($semesterId = $request->get('semester_id')) {
            $query->where('semester_id', $semesterId);
        }
        if ($search = $request->get('search')) {
            $query->whereHas('student.user', fn ($q) => $q->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
            );
        }

        $debts = $query->orderByDesc('debt_date')->paginate(25)->withQueryString();

        $groups = Group::whereHas('specialty', fn ($q) => $q->where('faculty_id', $facultyId))
            ->active()->orderBy('name')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        $stats = [
            'total_open' => (clone $query)->count(),
            'active' => AcademicDebt::whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
                )->count(),
            'retake_scheduled' => AcademicDebt::where('status', DebtStatus::RETAKE_SCHEDULED)
                ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
                )->count(),
            'overdue' => AcademicDebt::overdue()
                ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
                )->count(),
            'resolved_this_month' => AcademicDebt::where('status', DebtStatus::RESOLVED)
                ->where('resolved_date', '>=', now()->startOfMonth())
                ->whereHas('student', fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId))
                )->count(),
        ];

        return view('management.debts.index', compact('debts', 'groups', 'semesters', 'stats'));
    }

    public function show(AcademicDebt $debt): View
    {
        $facultyId = $this->facultyId();

        if ($debt->student?->specialty?->faculty?->id !== $facultyId) {
            abort(403, 'Шумо ба ин саҳифа дастрасӣ надоред.');
        }

        $debt->load([
            'student.user',
            'student.group',
            'subject',
            'semester',
            'subjectAssignment',
            'semesterGrade',
            'history.performedBy',
        ]);

        return view('management.debts.show', compact('debt'));
    }
}
