<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ContractPayment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Specialty;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountantDashboardController extends Controller
{
    public function index(): View
    {
        $contractStudents = Student::where('education_form', 'contract')->count();
        $totalExpected = (float) Student::where('education_form', 'contract')->sum('contract_amount');
        $totalCollected = (float) ContractPayment::sum('amount');
        $totalRemaining = max(0, $totalExpected - $totalCollected);

        $recentPayments = ContractPayment::with(['student.group', 'student.user', 'academicYear', 'recordedBy'])
            ->latest()
            ->limit(10)
            ->get();

        return view('accountant.dashboard', compact(
            'contractStudents', 'totalExpected', 'totalCollected', 'totalRemaining', 'recentPayments'
        ));
    }

    public function students(Request $request): View
    {
        $query = Student::where('education_form', 'contract')
            ->with(['user', 'group.specialty.faculty', 'group.course', 'contractPayments']);

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_id_number', 'like', "%{$search}%");
            });
        }

        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }

        if ($yearId = $request->get('academic_year_id')) {
            $query->whereHas('contractPayments', function ($q) use ($yearId) {
                $q->where('academic_year_id', $yearId);
            });
        }

        $students = $query->latest()->paginate(25)->withQueryString();
        $groups = Group::active()->with('specialty.faculty', 'course')->orderBy('name')->get();
        $years = AcademicYear::orderByDesc('id')->get();

        return view('accountant.students.index', compact('students', 'groups', 'years'));
    }

    public function show(Student $student): View
    {
        abort_unless($student->education_form === 'contract', 404);

        $student->load([
            'user',
            'group.specialty.faculty',
            'group.course',
            'contractPayments.academicYear',
            'contractPayments.recordedBy',
        ]);

        $years = AcademicYear::orderByDesc('id')->get();

        return view('accountant.students.show', compact('student', 'years'));
    }

    public function storePayment(Request $request, Student $student)
    {
        abort_unless($student->education_form === 'contract', 404);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:500',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $overpaid = ($student->contract_paid + $validated['amount']) > (float) ($student->contract_amount ?? 0);

        ContractPayment::create([
            'student_id' => $student->id,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'note' => $validated['note'] ?? null,
            'recorded_by' => auth()->id(),
        ]);

        $msg = "Пардохти {$validated['amount']} барои {$student->user->full_name} сабт шуд.";
        if ($overpaid) {
            $msg .= ' Даршав: маблағи супоридашуда аз маблағи шартнома зиёд аст.';
        }

        return redirect()->route('accountant.students.show', $student)
            ->with('success', $msg);
    }

    public function groupReport(Request $request): View
    {
        $facultyId = $request->get('faculty_id');
        $specialtyId = $request->get('specialty_id');
        $courseId = $request->get('course_id');
        $groupId = $request->get('group_id');

        $faculties = Faculty::orderBy('name')->get();
        $specialties = Specialty::when($facultyId, fn ($q) => $q->where('faculty_id', $facultyId))
            ->orderBy('name')->get();
        $courses = Course::orderBy('number')->get();

        $filterGroups = Group::query()
            ->when($facultyId, fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId)))
            ->when($specialtyId, fn ($q) => $q->where('specialty_id', $specialtyId))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $groups = Group::active()
            ->when($facultyId, fn ($q) => $q->whereHas('specialty', fn ($q2) => $q2->where('faculty_id', $facultyId)))
            ->when($specialtyId, fn ($q) => $q->where('specialty_id', $specialtyId))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->when($groupId, fn ($q) => $q->where('id', $groupId))
            ->with('specialty.faculty', 'course')
            ->orderBy('name')
            ->get();

        $report = $groups->map(function ($group) {
            $contractStudents = Student::where('group_id', $group->id)
                ->where('education_form', 'contract')
                ->count();
            $totalExpected = (float) Student::where('group_id', $group->id)
                ->where('education_form', 'contract')
                ->sum('contract_amount');
            $paid = (float) ContractPayment::whereHas('student', function ($q) use ($group) {
                $q->where('group_id', $group->id)->where('education_form', 'contract');
            })->sum('amount');
            $remaining = max(0, $totalExpected - $paid);

            return (object) [
                'group' => $group,
                'contract_students' => $contractStudents,
                'total_expected' => $totalExpected,
                'total_paid' => $paid,
                'total_remaining' => $remaining,
            ];
        })->filter(fn($r) => $r->contract_students > 0)
          ->sortBy(fn($r) => mb_strtolower($r->group->full_name))
          ->values();

        $totalExpected = (float) $report->sum('total_expected');
        $totalPaid = (float) $report->sum('total_paid');
        $totalRemaining = (float) $report->sum('total_remaining');

        return view('accountant.reports.groups', compact(
            'report',
            'faculties',
            'specialties',
            'courses',
            'filterGroups',
            'totalExpected',
            'totalPaid',
            'totalRemaining'
        ));
    }
}