<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\TeacherActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $query = Teacher::with(['user', 'department.faculty']);

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhere('employee_id', 'like', "%{$search}%");
        }

        if ($departmentId = $request->get('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($position = $request->get('position')) {
            $query->where('position', 'like', "%{$position}%");
        }

        $teachers = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();
        $departments = Department::active()->with('faculty')->orderBy('name')->get();

        return view('admin.teachers.index', compact('teachers', 'departments'));
    }

    public function create(): View
    {
        $departments = Department::active()->with('faculty')->orderBy('name')->get();
        return view('admin.teachers.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Корбар
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'login' => 'required|string|max:50|unique:users,login|alpha_dash',
            'password' => 'required|string|min:8|confirmed',
            'email' => 'nullable|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20',
            // Омӯзгор
            'department_id' => 'required|exists:departments,id',
            'employee_id' => 'required|string|max:30|unique:teachers,employee_id',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'academic_degree' => 'nullable|string|max:100',
            'academic_title' => 'nullable|string|max:100',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,hourly',
            'rate' => 'required|numeric|min:0.25|max:2.0',
            'hire_date' => 'required|date',
            'contract_end_date' => 'nullable|date|after:hire_date',
            'max_hours_per_week' => 'nullable|integer|min:4|max:72',
            'phone_work' => 'nullable|string|max:20',
            'biography' => 'nullable|string|max:2000',
        ], [
            'first_name.required' => 'Ном ҳатмӣ аст.',
            'last_name.required' => 'Насаб ҳатмӣ аст.',
            'login.required' => 'Логин ҳатмӣ аст.',
            'login.unique' => 'Ин логин аллакай мавҷуд аст.',
            'password.required' => 'Парол ҳатмӣ аст.',
            'department_id.required' => 'Кафедра ҳатмӣ аст.',
            'employee_id.required' => 'Рақами кормандӣ ҳатмӣ аст.',
            'employee_id.unique' => 'Ин рақами кормандӣ аллакай мавҷуд аст.',
            'position.required' => 'Вазифа ҳатмӣ аст.',
            'hire_date.required' => 'Санаи қабул ба кор ҳатмӣ аст.',
        ]);

        return DB::transaction(function () use ($validated) {
            // Корбар
            $user = User::create([
                'login' => $validated['login'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);

            // Нақши омӯзгор
            $teacherRole = Role::where('name', 'teacher')->first();
            $user->roles()->attach($teacherRole->id);

            // Омӯзгор
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'department_id' => $validated['department_id'],
                'employee_id' => $validated['employee_id'],
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'academic_degree' => $validated['academic_degree'] ?? null,
                'academic_title' => $validated['academic_title'] ?? null,
                'position' => $validated['position'],
                'employment_type' => $validated['employment_type'],
                'rate' => $validated['rate'],
                'hire_date' => $validated['hire_date'],
                'contract_end_date' => $validated['contract_end_date'] ?? null,
                'max_hours_per_week' => $validated['max_hours_per_week'] ?? 36,
                'phone_work' => $validated['phone_work'] ?? null,
                'biography' => $validated['biography'] ?? null,
                'status' => 'active',
            ]);

            // Лог
            TeacherActivityLog::create([
                'teacher_id' => $teacher->id,
                'activity_type' => 'hired',
                'description' => "Ба кор қабул шуд: {$validated['position']}",
                'activity_date' => $validated['hire_date'],
                'created_by' => auth()->id(),
            ]);

            AuditLog::log('create', "Омӯзгори нав: {$user->full_name}", Teacher::class, $teacher->id);

            return redirect()->route('admin.teachers.index')
                ->with('success', "Омӯзгор «{$user->full_name}» бомуваффақият сабт шуд.");
        });
    }

    public function show(Teacher $teacher): View
    {
        $teacher->load([
            'user.roles',
            'department.faculty',
            'subjectAssignments.subject',
            'subjectAssignments.group',
            'subjectAssignments.semester',
            'activityLog',
        ]);

        $currentSemester = \App\Models\Semester::current();
        $currentAssignments = $teacher->subjectAssignments()
            ->when($currentSemester, fn($q) => $q->where('semester_id', $currentSemester->id))
            ->where('is_active', true)
            ->with(['subject', 'group'])
            ->get();

        return view('admin.teachers.show', compact('teacher', 'currentAssignments', 'currentSemester'));
    }

    public function edit(Teacher $teacher): View
    {
        $teacher->load('user');
        $departments = Department::active()->with('faculty')->orderBy('name')->get();

        return view('admin.teachers.edit', compact('teacher', 'departments'));
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => "nullable|email|max:100|unique:users,email,{$teacher->user_id}",
            'phone' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'employee_id' => "required|string|max:30|unique:teachers,employee_id,{$teacher->id}",
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'academic_degree' => 'nullable|string|max:100',
            'academic_title' => 'nullable|string|max:100',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,hourly',
            'rate' => 'required|numeric|min:0.25|max:2.0',
            'hire_date' => 'required|date',
            'contract_end_date' => 'nullable|date|after:hire_date',
            'max_hours_per_week' => 'nullable|integer|min:4|max:72',
            'phone_work' => 'nullable|string|max:20',
            'biography' => 'nullable|string|max:2000',
            'status' => 'required|in:active,on_leave,dismissed',
        ]);

        DB::transaction(function () use ($teacher, $validated) {
            // Навсозии корбар
            $teacher->user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);

            // Сабти тағйири кафедра
            if ($teacher->department_id !== (int)$validated['department_id']) {
                TeacherActivityLog::create([
                    'teacher_id' => $teacher->id,
                    'activity_type' => 'department_change',
                    'description' => "Кафедра тағйир ёфт",
                    'activity_date' => now(),
                    'created_by' => auth()->id(),
                ]);
            }

            // Сабти тағйири вазифа
            if ($teacher->position !== $validated['position']) {
                TeacherActivityLog::create([
                    'teacher_id' => $teacher->id,
                    'activity_type' => 'promotion',
                    'description' => "Вазифа тағйир ёфт: {$teacher->position} → {$validated['position']}",
                    'activity_date' => now(),
                    'created_by' => auth()->id(),
                ]);
            }

            // Навсозии омӯзгор
            $teacher->update([
                'department_id' => $validated['department_id'],
                'employee_id' => $validated['employee_id'],
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'academic_degree' => $validated['academic_degree'] ?? null,
                'academic_title' => $validated['academic_title'] ?? null,
                'position' => $validated['position'],
                'employment_type' => $validated['employment_type'],
                'rate' => $validated['rate'],
                'hire_date' => $validated['hire_date'],
                'contract_end_date' => $validated['contract_end_date'] ?? null,
                'max_hours_per_week' => $validated['max_hours_per_week'] ?? 36,
                'phone_work' => $validated['phone_work'] ?? null,
                'biography' => $validated['biography'] ?? null,
                'status' => $validated['status'],
            ]);

            // Агар dismissed — корбарро inactive кун
            if ($validated['status'] === 'dismissed') {
                $teacher->user->update(['status' => 'inactive']);
            }
        });

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'Маълумоти омӯзгор навсозӣ шуд.');
    }
}
