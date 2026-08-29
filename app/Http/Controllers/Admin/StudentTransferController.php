<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentTransferController extends Controller
{
    public function index(Request $request): View
    {
        $query = StudentTransfer::with(['student.user', 'fromGroup', 'toGroup', 'fromSpecialty', 'toSpecialty', 'createdBy'])
            ->orderByDesc('transfer_date');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('from_specialty_id')) {
            $query->where('from_specialty_id', $request->from_specialty_id);
        }

        if ($request->filled('to_specialty_id')) {
            $query->where('to_specialty_id', $request->to_specialty_id);
        }

        $transfers = $query->paginate(20);
        $students = Student::with('user')->get();
        $specialties = Specialty::orderBy('name')->get();

        return view('admin.student-transfers.index', compact(
            'transfers',
            'students',
            'specialties'
        ));
    }

    public function create(Request $request): View
    {
        $studentId = $request->get('student_id');
        $student = null;

        if ($studentId) {
            $student = Student::with(['user', 'group', 'specialty'])->find($studentId);
        }

        $groups = Group::where('is_active', true)->orderBy('name')->get();
        $specialties = Specialty::where('is_active', true)->orderBy('name')->get();

        return view('admin.student-transfers.create', compact(
            'student',
            'groups',
            'specialties'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'from_group_id' => 'required|exists:groups,id',
            'to_group_id' => 'required|exists:groups,id',
            'from_specialty_id' => 'required|exists:specialties,id',
            'to_specialty_id' => 'required|exists:specialties,id',
            'transfer_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
            'order_number' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $transfer = null;

        DB::transaction(function () use ($validated, $student, &$transfer) {
            $transfer = StudentTransfer::create([
                'student_id' => $student->id,
                'from_group_id' => $validated['from_group_id'],
                'to_group_id' => $validated['to_group_id'],
                'from_specialty_id' => $validated['from_specialty_id'],
                'to_specialty_id' => $validated['to_specialty_id'],
                'transfer_date' => $validated['transfer_date'],
                'reason' => $validated['reason'],
                'order_number' => $validated['order_number'],
                'note' => $validated['note'],
                'created_by' => Auth::id(),
            ]);

            $student->update([
                'group_id' => $validated['to_group_id'],
                'specialty_id' => $validated['to_specialty_id'],
            ]);
        });

        return redirect()->route('admin.student-transfers.show', $transfer)
            ->with('success', 'Гузариши донишҷӯ бомуваффақият анҷом шуд.');
    }

    public function show(StudentTransfer $transfer): View
    {
        $transfer->load([
            'student.user',
            'fromGroup',
            'toGroup',
            'fromSpecialty',
            'toSpecialty',
            'createdBy'
        ]);

        return view('admin.student-transfers.show', compact('transfer'));
    }
}
