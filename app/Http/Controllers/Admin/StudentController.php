<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\StudentStatus;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Group;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\StudentPromotion;
use App\Models\StudentStatusHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with(['user', 'group', 'specialty', 'course']);

        // Ҷустуҷӯ
        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('login', 'like', "%{$search}%");
            })->orWhere('student_id_number', 'like', "%{$search}%");
        }

        // Филтрҳо
        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($specialtyId = $request->get('specialty_id')) {
            $query->where('specialty_id', $specialtyId);
        }
        if ($courseId = $request->get('course_id')) {
            $query->where('course_id', $courseId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($request->get('has_debts') === '1') {
            $query->where('has_debts', true);
        }
        if ($educationForm = $request->get('education_form')) {
            $query->where('education_form', $educationForm);
        }

        $students = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();

        $groups = Group::active()->orderBy('name')->get();
        $specialties = Specialty::active()->get();
        $courses = Course::orderBy('number')->get();

        return view('admin.students.index', compact('students', 'groups', 'specialties', 'courses'));
    }

    public function create(): View
    {
        $groups = Group::active()->with('specialty.department.faculty', 'course')->orderBy('name')->get();
        $specialties = Specialty::active()->with('department.faculty')->get();
        $courses = Course::orderBy('number')->get();

        return view('admin.students.create', compact('groups', 'specialties', 'courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Маълумоти корбар
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:4|confirmed',
            'email' => 'nullable|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20',
            // Маълумоти донишҷӯ
            'group_id' => 'required|exists:groups,id',
            'specialty_id' => 'required|exists:specialties,id',
            'course_id' => 'required|exists:courses,id',
            'student_id_number' => 'required|string|max:30|unique:students,student_id_number',
            'record_book_number' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:50',
            'passport_series' => 'nullable|string|max:10',
            'passport_number' => 'nullable|string|max:20',
            'inn' => 'nullable|string|max:20',
            'address_permanent' => 'nullable|string|max:500',
            'address_current' => 'nullable|string|max:500',
            'parent_phone' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:200',
            'education_form' => 'required|in:budget,contract',
            'study_form' => 'required|in:full_time,part_time,evening',
            'enrollment_date' => 'required|date',
            'enrollment_order' => 'nullable|string|max:50',
        ], [
            'first_name.required' => 'Ном ҳатмӣ аст.',
            'last_name.required' => 'Насаб ҳатмӣ аст.',
            'password.required' => 'Парол ҳатмӣ аст.',
            'password.min' => 'Парол бояд ҳадди ақал 4 рамз бошад.',
            'password.confirmed' => 'Тасдиқи парол мувофиқат намекунад.',
            'group_id.required' => 'Гурӯҳ ҳатмӣ аст.',
            'specialty_id.required' => 'Ихтисос ҳатмӣ аст.',
            'course_id.required' => 'Курс ҳатмӣ аст.',
            'student_id_number.required' => 'ID донишҷӯӣ ҳатмӣ аст.',
            'student_id_number.unique' => 'Ин ID донишҷӯӣ аллакай мавҷуд аст.',
            'enrollment_date.required' => 'Санаи қабул ҳатмӣ аст.',
            'education_form.required' => 'Шакли таҳсил ҳатмӣ аст.',
        ]);

        return DB::transaction(function () use ($validated) {
            // Сохтани корбар (логин = student_id_number автоматикӣ)
            $user = User::create([
                'login' => $validated['student_id_number'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'password' => Hash::make('12345678'),
                'must_change_password' => true,
                'status' => 'active',
            ]);

            // Таъинотии нақши донишҷӯ
            $studentRole = Role::where('name', 'student')->first();
            $user->roles()->attach($studentRole->id);

            // Сохтани донишҷӯ
            $student = Student::create([
                'user_id' => $user->id,
                'group_id' => $validated['group_id'],
                'specialty_id' => $validated['specialty_id'],
                'course_id' => $validated['course_id'],
                'student_id_number' => $validated['student_id_number'],
                'record_book_number' => $validated['record_book_number'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'citizenship' => $validated['citizenship'] ?? null,
                'passport_series' => $validated['passport_series'] ?? null,
                'passport_number' => $validated['passport_number'] ?? null,
                'inn' => $validated['inn'] ?? null,
                'address_permanent' => $validated['address_permanent'] ?? null,
                'address_current' => $validated['address_current'] ?? null,
                'parent_phone' => $validated['parent_phone'] ?? null,
                'parent_name' => $validated['parent_name'] ?? null,
                'education_form' => $validated['education_form'],
                'study_form' => $validated['study_form'],
                'enrollment_date' => $validated['enrollment_date'],
                'enrollment_order' => $validated['enrollment_order'] ?? null,
                'status' => StudentStatus::ACTIVE,
                'status_date' => now(),
            ]);

            AuditLog::log('create', "Донишҷӯи нав: {$user->full_name} ({$validated['student_id_number']})", Student::class, $student->id);

            return redirect()->route('admin.students.index')
                ->with('success', "Донишҷӯ «{$user->full_name}» бомуваффақият сабт шуд.");
        });
    }

    public function show(Student $student): View
    {
        $student->load([
            'user',
            'group.specialty.department.faculty',
            'specialty',
            'course',
            'statusHistory.createdByUser',
            'promotions.fromGroup',
            'promotions.toGroup',
            'semesterGrades.subject',
            'semesterGrades.semester',
            'activeDebts.subject',
            'semesterGpas.semester',
        ]);

        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $student->load('user');
        $groups = Group::active()->with('specialty.department.faculty', 'course')->orderBy('name')->get();
        $specialties = Specialty::active()->with('department.faculty')->get();
        $courses = Course::orderBy('number')->get();

        return view('admin.students.edit', compact('student', 'groups', 'specialties', 'courses'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => "nullable|email|max:100|unique:users,email,{$student->user_id}",
            'phone' => 'nullable|string|max:20',
            'group_id' => 'required|exists:groups,id',
            'specialty_id' => 'required|exists:specialties,id',
            'course_id' => 'required|exists:courses,id',
            'student_id_number' => "required|string|max:30|unique:students,student_id_number,{$student->id}",
            'record_book_number' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:50',
            'passport_series' => 'nullable|string|max:10',
            'passport_number' => 'nullable|string|max:20',
            'inn' => 'nullable|string|max:20',
            'address_permanent' => 'nullable|string|max:500',
            'address_current' => 'nullable|string|max:500',
            'parent_phone' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:200',
            'education_form' => 'required|in:budget,contract',
            'study_form' => 'required|in:full_time,part_time,evening',
            'enrollment_date' => 'required|date',
            'enrollment_order' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($student, $validated) {
            // Навсозии корбар
            $student->user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);

            // Навсозии донишҷӯ
            $student->update([
                'group_id' => $validated['group_id'],
                'specialty_id' => $validated['specialty_id'],
                'course_id' => $validated['course_id'],
                'student_id_number' => $validated['student_id_number'],
                'record_book_number' => $validated['record_book_number'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'citizenship' => $validated['citizenship'] ?? null,
                'passport_series' => $validated['passport_series'] ?? null,
                'passport_number' => $validated['passport_number'] ?? null,
                'inn' => $validated['inn'] ?? null,
                'address_permanent' => $validated['address_permanent'] ?? null,
                'address_current' => $validated['address_current'] ?? null,
                'parent_phone' => $validated['parent_phone'] ?? null,
                'parent_name' => $validated['parent_name'] ?? null,
                'education_form' => $validated['education_form'],
                'study_form' => $validated['study_form'],
                'enrollment_date' => $validated['enrollment_date'],
                'enrollment_order' => $validated['enrollment_order'] ?? null,
            ]);
        });

        return redirect()->route('admin.students.show', $student)
            ->with('success', 'Маълумоти донишҷӯ навсозӣ шуд.');
    }

    /**
     * Тағйири ҳолати донишҷӯ (хориҷ, рухсатӣ, барқарор, хатм, ...)
     */
    public function changeStatus(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'new_status' => 'required|in:active,academic_leave,expelled,graduated,transferred,restored,suspended',
            'reason' => 'required|string|max:500',
            'order_number' => 'nullable|string|max:50',
            'order_date' => 'nullable|date',
        ], [
            'new_status.required' => 'Ҳолати нав ҳатмӣ аст.',
            'reason.required' => 'Сабаб ҳатмӣ аст.',
        ]);

        $oldStatus = $student->status;
        $newStatus = StudentStatus::from($validated['new_status']);

        DB::transaction(function () use ($student, $oldStatus, $newStatus, $validated) {
            // Навсозии ҳолат
            $student->update([
                'status' => $newStatus,
                'status_date' => now(),
                'status_order' => $validated['order_number'] ?? null,
                'status_reason' => $validated['reason'],
            ]);

            // Навсозии корбар (агар хориҷ ё рухсатӣ — inactive кун)
            if (in_array($newStatus, [StudentStatus::EXPELLED, StudentStatus::SUSPENDED])) {
                $student->user->update(['status' => 'inactive']);
            } elseif ($newStatus === StudentStatus::ACTIVE) {
                $student->user->update(['status' => 'active']);
            }

            // Сабти таърих
            StudentStatusHistory::create([
                'student_id' => $student->id,
                'from_status' => $oldStatus?->value ?? null,
                'to_status' => $newStatus->value,
                'order_number' => $validated['order_number'] ?? null,
                'order_date' => $validated['order_date'] ?? now(),
                'reason' => $validated['reason'],
                'created_by' => auth()->id(),
            ]);

            AuditLog::log(
                'update',
                "Ҳолати донишҷӯ тағйир ёфт: {$student->user->full_name} → {$newStatus->label()}",
                Student::class,
                $student->id
            );
        });

        return back()->with('success', "Ҳолати донишҷӯ ба «{$newStatus->label()}» тағйир ёфт.");
    }

    /**
     * Гузаронидан ба курси нав (promote)
     */
    public function promote(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'new_group_id' => 'required|exists:groups,id',
            'new_course_id' => 'required|exists:courses,id',
            'order_number' => 'nullable|string|max:50',
            'order_date' => 'nullable|date',
        ], [
            'new_group_id.required' => 'Гурӯҳи нав ҳатмӣ аст.',
            'new_course_id.required' => 'Курси нав ҳатмӣ аст.',
        ]);

        // Санҷиш: бояд фаъол бошад
        if (!$student->isActive()) {
            return back()->with('error', 'Танҳо донишҷӯёни фаъолро гузаронидан мумкин аст.');
        }

        DB::transaction(function () use ($student, $validated) {
            $oldGroupId = $student->group_id;
            $oldCourseId = $student->course_id;

            // Навсозии гурӯҳ ва курс
            $student->update([
                'group_id' => $validated['new_group_id'],
                'course_id' => $validated['new_course_id'],
            ]);

            // Сабти гузариш
            StudentPromotion::create([
                'student_id' => $student->id,
                'from_group_id' => $oldGroupId,
                'to_group_id' => $validated['new_group_id'],
                'from_course_id' => $oldCourseId,
                'to_course_id' => $validated['new_course_id'],
                'academic_year_id' => \App\Models\AcademicYear::current()?->id ?? 1,
                'order_number' => $validated['order_number'] ?? null,
                'order_date' => $validated['order_date'] ?? now(),
                'gpa_at_promotion' => $student->cumulative_gpa,
                'created_by' => auth()->id(),
            ]);

            AuditLog::log(
                'update',
                "Донишҷӯ гузаронида шуд ба курси нав: {$student->user->full_name}",
                Student::class,
                $student->id
            );
        });

        return back()->with('success', 'Донишҷӯ ба курси нав гузаронида шуд.');
    }
}
