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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        // Оптимизатсияи Eager Loading бо муносибатҳои лозимӣ
        $query = Student::with([
            'user:id,first_name,last_name,middle_name,login,status', 
            'group:id,name,specialty_id,course_id', 
            'group.specialty:id,name',
            'specialty:id,name', 
            'course:id,number'
        ]);

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

    public function search(Request $request)
    {
        $search = $request->get('search', '');
        $groupId = $request->get('group_id');
        $courseId = $request->get('course_id');
        $status = $request->get('status');

        $query = Student::with([
            'user:id,first_name,last_name,middle_name', 
            'group:id,name', 
            'course:id,number'
        ])->limit(20);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('login', 'like', "%{$search}%");
            })->orWhere('student_id_number', 'like', "%{$search}%");
        }

        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $students = $query->get();

        $results = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->user?->full_name ?? '—',
                'student_id' => $student->student_id_number,
                'group' => $student->group?->name ?? '—',
                'course' => $student->course?->name ?? '—',
                'url' => route('admin.students.show', $student),
            ];
        });

        return response()->json($results);
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

            return redirect()->route('admin.students.show', $student)
                ->with('success', "Донишҷӯ «{$user->full_name}» бомуваффақият сабт шуд.");
        });
    }

    public function show(Student $student): View
    {
        $student->load([
            'user',
            'group.specialty.department.faculty',
            'specialty.department.faculty',
            'course',
            'statusHistory' => fn($q) => $q->with('createdByUser:id,first_name,last_name')->latest(),
            'promotions' => fn($q) => $q->with(['fromGroup', 'toGroup', 'fromCourse', 'toCourse'])->latest(),
            'semesterGrades' => fn($q) => $q->with(['subjectAssignment.subject', 'semester'])->orderByDesc('semester_id'),
            'activeDebts' => fn($q) => $q->with('subject')->latest(),
            'semesterGpas' => fn($q) => $q->with('semester')->orderByDesc('semester_id'),
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
            'orphan_type' => 'nullable|in:none,orphan,half_orphan',
            'guardian_name' => 'nullable|string|max:200',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_relation' => 'nullable|string|max:50',
        ]);

        $loginExists = User::where('login', $validated['student_id_number'])
            ->whereKeyNot($student->user_id)
            ->exists();

        if ($loginExists) {
            return back()->withErrors([
                'student_id_number' => 'Ин рақами донишҷӯӣ аллакай ҳамчун login истифода мешавад.',
            ])->withInput();
        }

        DB::transaction(function () use ($student, $validated) {
            // Навсозии корбар
            $student->user->update([
                'login' => $validated['student_id_number'],
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
                'orphan_type' => $validated['orphan_type'] ?? 'none',
                'guardian_name' => $validated['guardian_name'] ?? null,
                'guardian_phone' => $validated['guardian_phone'] ?? null,
                'guardian_relation' => $validated['guardian_relation'] ?? null,
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

    /**
     * Саҳифаи импорти донишҷӯён
     */
    public function importForm(): View
    {
        $groups = Group::with(['specialty', 'course'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.students.import', compact('groups'));
    }

    /**
     * Импорти донишҷӯён аз Excel
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'group_id' => 'required|exists:groups,id',
        ]);

        $file = $request->file('file');
        $groupId = $request->input('group_id');

        $rows = $this->parseExcel($file->getRealPath());

        if (empty($rows)) {
            return back()->with('error', 'Файл холӣ аст ё формати нодуруст дорад.');
        }

        $group = Group::findOrFail($groupId);
        $specialties = Specialty::whereIn('name', collect($rows)->pluck('specialty_name')->filter()->unique())
            ->get()
            ->keyBy('name');
        $courses = Course::whereIn('number', collect($rows)->pluck('course_name')->filter()->unique())
            ->get()
            ->keyBy('number');
        $existingEmails = User::whereIn('email', collect($rows)->pluck('email')->filter()->unique())
            ->pluck('email')
            ->flip();
        $existingStudentNumbers = Student::whereIn('student_id_number', collect($rows)->pluck('student_id_number')->filter()->unique())
            ->pluck('student_id_number')
            ->flip();
        $explicitNumbers = collect($rows)->pluck('student_id_number')->filter()->unique()->values();
        $existingLogins = User::where(function ($query) use ($explicitNumbers) {
                $query->whereIn('login', $explicitNumbers)
                    ->orWhere('login', 'like', 'ST-' . date('Y') . '-%');
            })
            ->pluck('login')
            ->flip();
        $nextGeneratedNumber = (int) User::max('id') + 1;

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $generatedPasswords = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $validator = Validator::make($row, [
                    'last_name' => 'required|string|max:100',
                    'first_name' => 'required|string|max:100',
                    'middle_name' => 'nullable|string|max:100',
                    'email' => 'nullable|email|max:150',
                    'phone' => 'nullable|string|max:20',
                    'birth_date' => 'nullable|date_format:d.m.Y',
                    'gender' => 'nullable|string|max:20',
                    'student_id_number' => 'nullable|string|max:50',
                    'passport_number' => 'nullable|string|max:20',
                    'record_book_number' => 'nullable|string|max:30',
                    'nationality' => 'nullable|string|max:50',
                    'citizenship' => 'nullable|string|max:50',
                    'passport_series' => 'nullable|string|max:10',
                    'parent_name' => 'nullable|string|max:200',
                    'parent_phone' => 'nullable|string|max:20',
                    'address_permanent' => 'nullable|string|max:500',
                    'address_current' => 'nullable|string|max:500',
                    'education_form' => 'nullable|string|max:50',
                    'study_form' => 'nullable|string|max:50',
                    'enrollment_date' => 'nullable|date_format:d.m.Y',
                    'specialty_name' => 'nullable|string|max:100',
                    'course_name' => 'nullable|string|max:50',
                    'group_name' => 'nullable|string|max:50',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Сатри {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    $skipped++;
                    continue;
                }

                if (!empty($row['email'])) {
                    if ($existingEmails->has($row['email'])) {
                        $errors[] = "Сатри {$rowNumber}: Email «{$row['email']}» аллакай мавҷуд аст.";
                        $skipped++;
                        continue;
                    }
                }

                if (!empty($row['student_id_number'])) {
                    if ($existingStudentNumbers->has($row['student_id_number'])) {
                        $errors[] = "Сатри {$rowNumber}: Рақами «{$row['student_id_number']}» аллакай мавҷуд аст.";
                        $skipped++;
                        continue;
                    }
                }

                $email = $row['email'] ?? null;
                if (empty($email)) {
                    $email = Str::slug($row['first_name'] . '.' . $row['last_name']) . '.' . Str::random(4) . '@student.donishor.tj';
                }

                $studentNumber = !empty($row['student_id_number'])
                    ? trim($row['student_id_number'])
                    : 'ST-' . date('Y') . '-' . str_pad($nextGeneratedNumber++, 4, '0', STR_PAD_LEFT);

                if ($existingStudentNumbers->has($studentNumber)
                    || $existingLogins->has($studentNumber)) {
                    $errors[] = "Сатри {$rowNumber}: Рақами донишҷӯӣ «{$studentNumber}» аллакай мавҷуд аст.";
                    $skipped++;
                    continue;
                }

                $password = '12345678';

                $generatedPlainPassword = $password;

                $educationFormMap = [
                    'Буҷетӣ' => 'budget',
                    'Шартномавӣ' => 'contract',
                ];
                $studyFormMap = [
                    'Рӯзона' => 'full_time',
                    'Ғоибона' => 'part_time',
                    'Шабона' => 'evening',
                ];
                $genderMap = [
                    'Мард' => 'male',
                    'Зан' => 'female',
                ];

                $educationForm = $educationFormMap[$row['education_form'] ?? ''] ?? $row['education_form'] ?? null;
                $studyForm = $studyFormMap[$row['study_form'] ?? ''] ?? $row['study_form'] ?? null;
                $gender = $genderMap[$row['gender'] ?? ''] ?? $row['gender'] ?? null;

                $specialty = $specialties->get($row['specialty_name'] ?? '');
                $course = $courses->get($row['course_name'] ?? '');
                $specialtyId = $specialty?->id ?? ($group?->specialty_id ?? null);
                $courseId = $course?->id ?? ($group?->course_id ?? null);

                $birthDate = !empty($row['birth_date']) ? \Carbon\Carbon::createFromFormat('d.m.Y', $row['birth_date'])->format('Y-m-d') : null;
                $enrollmentDate = !empty($row['enrollment_date']) ? \Carbon\Carbon::createFromFormat('d.m.Y', $row['enrollment_date'])->format('Y-m-d') : null;

                $user = User::create([
                    'login' => $studentNumber,
                    'first_name' => trim($row['first_name']),
                    'last_name' => trim($row['last_name']),
                    'middle_name' => trim($row['middle_name'] ?? ''),
                    'email' => $email,
                    'phone' => $row['phone'] ?? null,
                    'password' => Hash::make($password),
                    'must_change_password' => true,
                    'is_active' => true,
                ]);

                $existingEmails->put($email, true);
                $existingStudentNumbers->put($studentNumber, true);
                $existingLogins->put($studentNumber, true);

                $user->roles()->attach(
                    \App\Models\Role::where('name', 'student')->first()?->id
                );

                Student::create([
                    'user_id' => $user->id,
                    'group_id' => $groupId,
                    'specialty_id' => $specialtyId,
                    'course_id' => $courseId,
                    'student_id_number' => $studentNumber,
                    'record_book_number' => $row['record_book_number'] ?? null,
                    'birth_date' => $birthDate,
                    'gender' => $gender,
                    'nationality' => $row['nationality'] ?? null,
                    'citizenship' => $row['citizenship'] ?? null,
                    'passport_series' => $row['passport_series'] ?? null,
                    'passport_number' => $row['passport_number'] ?? null,
                    'address_permanent' => $row['address_permanent'] ?? null,
                    'address_current' => $row['address_current'] ?? null,
                    'parent_phone' => $row['parent_phone'] ?? null,
                    'parent_name' => $row['parent_name'] ?? null,
                    'education_form' => $educationForm,
                    'study_form' => $studyForm,
                    'enrollment_date' => $enrollmentDate,
                    'status' => 'active',
                ]);

                $generatedPasswords[] = [
                    'student_id' => $studentNumber,
                    'name' => trim($row['last_name']) . ' ' . trim($row['first_name']),
                    'password' => $password,
                ];

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Хатогии система: ' . $e->getMessage());
        }

        $message = "{$imported} донишҷӯ бо муваффақият ворид шуд.";
        if ($skipped > 0) {
            $message .= " {$skipped} сатр гузаронида шуд.";
        }

        return redirect()->route('admin.students.index')
            ->with('success', $message)
            ->with('import_errors', $errors)
            ->with('generated_passwords', $generatedPasswords);
    }

    /**
     * Зеркашии шаблони Excel
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();

        // ==================== SHEET 1: Донишҷӯён ====================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Донишҷӯён');

        $headers = [
            'Насаб *',
            'Ном *',
            'Номи падар',
            'Email',
            'ID донишҷӯӣ *',
            'Рақами зачётка',
            'Шакли таъмин *',
            'Шакли таҳсил *',
            'Ихтисос *',
            'Гурӯҳ *',
            'Курс *',
            'Санаи қабул *',
            'Санаи таваллуд',
            'Ҷинс',
            'Миллат',
            'Паспорт (серия)',
            'Паспорт (рақам)',
            'Телефон',
            'Волидон (ном)',
            'Телефони волидон',
            'Суроғаи доимӣ',
            'Суроғаи ҳозира',
        ];

        $example = [
            'Каримов',
            'Саид',
            'Саидович',
            'said@example.com',
            'STU2026001',
            '123456',
            'Буҷетӣ',
            'Рӯзона',
            'Кори тиббӣ12',
            'ТИ-1-24',
            '1',
            '01.09.2026',
            '15.05.2008',
            'Мард',
            'Тоҷик',
            'AA',
            '1234567',
            '900123456',
            'Каримов Абдураҳмон',
            '900765432',
            'ш. Бохтар, кӯчаи Рӯдакӣ 10',
            'ш. Бохтар, кӯчаи Сомонӣ 25',
        ];

        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->fromArray($example, NULL, 'A2');

        // Header style
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
        ];
        $sheet->getStyle('A1:V1')->applyFromArray($headerStyle);

        // Freeze header
        $sheet->freezePane('A2');

        // AutoFilter
        $sheet->setAutoFilter('A1:V1');

        // Column widths
        foreach (range('A', 'V') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Wrap text
        $sheet->getStyle('A1:V2')->getAlignment()->setWrapText(true);

        // Data validation
        $validation = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);

        $validation->setFormula1('"Буҷетӣ,Шартномавӣ"');
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('G' . $row)->setDataValidation(clone $validation);
        }

        $validation->setFormula1('"Рӯзона,Ғоибона,Шабона"');
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('H' . $row)->setDataValidation(clone $validation);
        }

        $validation->setFormula1('"1,2,3,4,5,6"');
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('K' . $row)->setDataValidation(clone $validation);
        }

        $validation->setFormula1('"Мард,Зан"');
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('N' . $row)->setDataValidation(clone $validation);
        }

        // Dynamic dropdowns for specialty and group from DB
        $specialties = Specialty::active()->orderBy('name')->pluck('name')->toArray();
        $specialtyList = implode(',', array_map('trim', $specialties));

        $validation->setFormula1('"' . $specialtyList . '"');
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('I' . $row)->setDataValidation(clone $validation);
        }

        $groups = Group::active()->orderBy('name')->pluck('name')->toArray();
        $groupList = implode(',', array_map('trim', $groups));

        $validation->setFormula1('"' . $groupList . '"');
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('J' . $row)->setDataValidation(clone $validation);
        }

        // ==================== SHEET 2: Маълумот ====================
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Маълумот');

        $infoHeaders = ['Сутун', 'Ҳатмӣ', 'Тавсиф', 'Қиматҳои иҷозатдодашуда'];
        $infoData = [
            ['Насаб *', 'Ҳа', 'Насаби донишҷӯ', ''],
            ['Ном *', 'Ҳа', 'Номи донишҷӯ', ''],
            ['Номи падар', 'Не', 'Номи падари донишҷӯ', ''],
            ['Email', 'Не', 'Почтаи электронӣ', ''],
            ['ID донишҷӯӣ *', 'Ҳа', 'Рақами идентификатсионии донишҷӯ', ''],
            ['Рақами зачётка', 'Не', 'Рақами зачётка', ''],
            ['Шакли таъмин *', 'Ҳа', 'Шакли маблағгузории таҳсил', 'Буҷетӣ / Шартномавӣ'],
            ['Шакли таҳсил *', 'Ҳа', 'Шакли таҳсил', 'Рӯзона / Ғоибона / Шабона'],
            ['Ихтисос *', 'Ҳа', 'Ихтисоси донишҷӯ', implode(', ', $specialties)],
            ['Гурӯҳ *', 'Ҳа', 'Гурӯҳи донишҷӯ', implode(', ', $groups)],
            ['Курс *', 'Ҳа', 'Курси донишҷӯ', '1 / 2 / 3 / 4 / 5 / 6'],
            ['Санаи қабул *', 'Ҳа', 'Санаи қабул ба донишгоҳ', 'DD.MM.YYYY'],
            ['Санаи таваллуд', 'Не', 'Санаи таваллуди донишҷӯ', 'DD.MM.YYYY'],
            ['Ҷинс', 'Не', 'Ҷинси донишҷӯ', 'Мард / Зан'],
            ['Миллат', 'Не', 'Миллати донишҷӯ', ''],
            ['Паспорт (серия)', 'Не', 'Серияи паспорт', ''],
            ['Паспорт (рақам)', 'Не', 'Рақами паспорт', ''],
            ['Телефон', 'Не', 'Телефони донишҷӯ', ''],
            ['Волидон (ном)', 'Не', 'Номи волидайн / накина', ''],
            ['Телефони волидон', 'Не', 'Телефони волидайн / накина', ''],
            ['Суроғаи доимӣ', 'Не', 'Суроғаи пойдор', ''],
            ['Суроғаи ҳозира', 'Не', 'Суроғаи муваққат', ''],
        ];

        $infoSheet->fromArray($infoHeaders, NULL, 'A1');
        $infoSheet->fromArray($infoData, NULL, 'A2');

        $infoSheet->getStyle('A1:D1')->getFont()->setBold(true);
        $infoSheet->getColumnDimension('A')->setWidth(25);
        $infoSheet->getColumnDimension('B')->setWidth(12);
        $infoSheet->getColumnDimension('C')->setWidth(40);
        $infoSheet->getColumnDimension('D')->setWidth(60);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = sys_get_temp_dir() . '/student_import_template_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download($tmpPath, 'student_import_template.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Mapping аз сарлавҳаҳои Excel ба ҳамаи майдонҳои backend
     */
    private function excelHeaderMapping(): array
    {
        return [
            'Насаб *' => 'last_name',
            'Ном *' => 'first_name',
            'Номи падар' => 'middle_name',
            'Email' => 'email',
            'ID донишҷӯӣ *' => 'student_id_number',
            'Рақами зачётка' => 'record_book_number',
            'Шакли таъмин *' => 'education_form',
            'Шакли таҳсил *' => 'study_form',
            'Ихтисос *' => 'specialty_name',
            'Гурӯҳ *' => 'group_name',
            'Курс *' => 'course_name',
            'Санаи қабул *' => 'enrollment_date',
            'Санаи таваллуд' => 'birth_date',
            'Ҷинс' => 'gender',
            'Миллат' => 'nationality',
            'Паспорт (серия)' => 'passport_series',
            'Паспорт (рақам)' => 'passport_number',
            'Телефон' => 'phone',
            'Волидон (ном)' => 'parent_name',
            'Телефони волидон' => 'parent_phone',
            'Суроғаи доимӣ' => 'address_permanent',
            'Суроғаи ҳозира' => 'address_current',
        ];
    }

    /**
     * Parse файли Excel ба массив
     */
    private function parseExcel(string $path): array
    {
        $rows = [];
        $headers = [];
        $mapping = $this->excelHeaderMapping();

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            return [];
        }

        if (empty($data)) {
            return [];
        }

        $rawHeaders = array_map('trim', $data[0]);
        $mappedHeaders = [];
        foreach ($rawHeaders as $header) {
            $mappedHeaders[] = $mapping[$header] ?? $header;
        }

        $rows = [];

        for ($i = 1; $i < count($data); $i++) {
            $row = [];
            foreach ($mappedHeaders as $j => $header) {
                $row[$header] = isset($data[$i][$j]) ? trim($data[$i][$j]) : null;
            }

            if (!empty($row['first_name']) || !empty($row['last_name']) || !empty($row['student_id_number'])) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
