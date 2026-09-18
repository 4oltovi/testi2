<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Group;
use App\Models\Setting;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentPromotion;
use App\Models\StudentStatusHistory;
use App\Enums\DebtStatus;
use App\Enums\StudentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Preview: dry-run promotion for all active students.
     */
    public function promoteAllPreview(): View
    {
        $categories = [
            'graduated'               => [],
            'graduated_with_debts'    => [],
            'promoted'                => [],
            'needs_review_no_group'   => [],
            'needs_review_no_duration' => [],
            'skipped_no_course'       => [],
            'on_leave_excluded'       => [],
        ];

        $students = Student::where('status', StudentStatus::ACTIVE)->with(['course', 'group', 'specialty', 'academicDebts'])->get();

        foreach ($students as $s) {
            $num = (int) ($s->course->number ?? preg_replace('/\D/', '', $s->course->name ?? '') ?: 0);

            if ($num <= 0) {
                $categories['skipped_no_course'][] = [
                    'student' => $s,
                    'reason' => 'Course number could not be determined',
                ];
                continue;
            }

            $specialty = $s->specialty;
            $duration = $specialty?->study_years;

            if ($duration === null || $duration <= 0) {
                $categories['needs_review_no_duration'][] = [
                    'student' => $s,
                    'reason' => 'Specialty duration not configured',
                ];
                continue;
            }

            if ($num >= $duration) {
                $openDebts = AcademicDebt::open()->where('student_id', $s->id)->count();
                if ($openDebts > 0) {
                    $categories['graduated_with_debts'][] = [
                        'student' => $s,
                        'open_debts' => $openDebts,
                    ];
                } else {
                    $categories['graduated'][] = ['student' => $s];
                }
                continue;
            }

            $next = Course::where('number', $num + 1)->first()
                ?? Course::where('name', 'like', '%' . ($num + 1) . '%')->first();

            if (!$next) {
                $categories['skipped_no_course'][] = [
                    'student' => $s,
                    'reason' => 'Next course not found',
                ];
                continue;
            }

            $nextGroup = Group::where('specialty_id', $s->specialty_id)
                ->where('course_id', $next->id)
                ->first();

            if (!$nextGroup) {
                $categories['needs_review_no_group'][] = [
                    'student' => $s,
                    'reason' => 'No matching group for specialty + next course',
                ];
                continue;
            }

            $categories['promoted'][] = [
                'student' => $s,
                'from_group' => $s->group,
                'to_group' => $nextGroup,
                'from_course' => $s->course,
                'to_course' => $next,
            ];
        }

        return view('admin.settings.promote-preview', compact('categories'));
    }

    /**
     * 🎓 Гузариш ба соли нав — ҳамаи донишҷӯён
     */
    public function promoteAll(Request $request): RedirectResponse
    {
        $results = [
            'promoted'               => [],
            'graduated'              => [],
            'graduated_with_debts'   => [],
            'needs_review_no_group'  => [],
            'needs_review_no_duration' => [],
            'skipped_no_course'      => [],
            'on_leave_excluded'      => [],
        ];

        Student::where('status', StudentStatus::ACTIVE)->with(['course', 'group', 'specialty', 'academicDebts'])->chunk(200, function ($students) use (&$results) {
            DB::transaction(function () use ($students, &$results) {
                foreach ($students as $s) {
                    $num = (int) ($s->course->number ?? preg_replace('/\D/', '', $s->course->name ?? '') ?: 0);

                    if ($num <= 0) {
                        $results['skipped_no_course'][] = $s->id;
                        continue;
                    }

                    $specialty = $s->specialty;
                    $duration = $specialty?->study_years;

                    if ($duration === null || $duration <= 0) {
                        $results['needs_review_no_duration'][] = $s->id;
                        continue;
                    }

                    if ($num >= $duration) {
                        $openDebts = AcademicDebt::open()->where('student_id', $s->id)->count();
                        if ($openDebts > 0) {
                            $results['graduated_with_debts'][] = $s->id;
                            continue;
                        }

                        $prevStatus = $s->status;
                        $s->update([
                            'status' => StudentStatus::GRADUATED,
                            'status_date' => now(),
                            'status_reason' => 'Хатми муваффақонаи курси таҳсил',
                        ]);

                        StudentStatusHistory::create([
                            'student_id' => $s->id,
                            'from_status' => $prevStatus->value,
                            'to_status' => StudentStatus::GRADUATED->value,
                            'reason' => 'Хатми муваффақонаи курси таҳсил',
                            'created_by' => auth()->id(),
                        ]);

                        AuditLog::log(
                            'promote',
                            "Донишҷӯ хатм кард: {$s->user?->full_name}",
                            Student::class,
                            $s->id,
                            ['status' => $prevStatus->value, 'course_id' => $s->course_id],
                            ['status' => StudentStatus::GRADUATED->value]
                        );

                        $results['graduated'][] = $s->id;
                        continue;
                    }

                    $next = Course::where('number', $num + 1)->first()
                        ?? Course::where('name', 'like', '%' . ($num + 1) . '%')->first();

                    if (!$next) {
                        $results['skipped_no_course'][] = $s->id;
                        continue;
                    }

                    $nextGroup = Group::where('specialty_id', $s->specialty_id)
                        ->where('course_id', $next->id)
                        ->first();

                    if (!$nextGroup) {
                        $results['needs_review_no_group'][] = $s->id;
                        continue;
                    }

                    $oldGroupId = $s->group_id;
                    $oldCourseId = $s->course_id;

                    $s->update([
                        'course_id' => $next->id,
                        'group_id' => $nextGroup->id,
                    ]);

                    StudentPromotion::create([
                        'student_id' => $s->id,
                        'from_group_id' => $oldGroupId,
                        'to_group_id' => $nextGroup->id,
                        'from_course_id' => $oldCourseId,
                        'to_course_id' => $next->id,
                        'academic_year_id' => AcademicYear::current()?->id ?? 1,
                        'gpa_at_promotion' => $s->cumulative_gpa,
                        'created_by' => auth()->id(),
                    ]);

                    AuditLog::log(
                        'promote',
                        "Донишҷӯ гузаронида шуд: {$s->user?->full_name}",
                        Student::class,
                        $s->id,
                        ['group_id' => $oldGroupId, 'course_id' => $oldCourseId],
                        ['group_id' => $nextGroup->id, 'course_id' => $next->id]
                    );

                    $results['promoted'][] = $s->id;
                }
            });
        });

        $totalProcessed = array_sum(array_map('count', $results));

        return back()->with('success', "🎓 Гузариш ба соли нав: {$totalProcessed} донишҷӯ обработ шуданд.")->with('results', $results);
    }

    /**
     * Ҳамаи танзимот (умумӣ)
     */
    public function index(): View
    {
        $formulaSettings      = Setting::where('group', 'formula')->orderBy('key')->get();
        $testSettings         = Setting::where('group', 'test')->orderBy('key')->get();
        $organizationSettings = Setting::where('group', 'organization')->orderBy('key')->get();
        $securitySettings     = Setting::where('group', 'security')->orderBy('key')->get();
        $academicYears        = AcademicYear::orderBy('id', 'desc')->get();
        $semesters            = Semester::with('academicYear')->orderBy('academic_year_id')->orderBy('number')->get();

        return view('admin.settings.index', compact(
            'formulaSettings',
            'testSettings',
            'organizationSettings',
            'securitySettings',
            'academicYears',
            'semesters'
        ));
    }

    /**
     * Саҳифаи танзимоти формулаҳо
     */
    public function formula(): View
    {
        $settings = Setting::where('group', 'formula')->orderBy('key')->get();
        return view('admin.settings.formula', compact('settings'));
    }

    /**
     * Саҳифаи танзимоти тест
     */
    public function test(): View
    {
        $settings = Setting::where('group', 'test')->orderBy('key')->get();
        return view('admin.settings.test', compact('settings'));
    }

    /**
     * Навсозии танзимот
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings'   => 'required|array',
            'settings.*' => 'required',
        ]);

        foreach ($request->input('settings') as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Танзимот бо муваффақият сабт шуд.');
    }

    /**
     * Бор кардани логотипи муассиса
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if (!is_dir(public_path('images'))) {
            mkdir(public_path('images'), 0777, true);
        }

        $file = $request->file('logo');
        $name = 'logo.' . $file->getClientOriginalExtension();
        $file->move(public_path('images'), $name);

        Setting::updateOrCreate(
            ['key' => 'institution_logo'],
            [
                'value'        => 'images/' . $name,
                'type'         => 'string',
                'group'        => 'organization',
                'display_name' => 'Логотипи муассиса',
                'description'  => 'Дар барнома, ведомост ва транскрипт истифода мешавад',
                'is_public'    => 1,
            ]
        );

        return back()->with('success', 'Логотип бо муваффақият сабт шуд.');
    }

    /**
     * ⚡ Оптимизатсия (кеш) — барои сервер
     */
    public function optimize(): RedirectResponse
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return back()->with('success', '⚡ Оптимизатсия иҷро шуд — система зудтар кор мекунад!');
    }

    /**
     * 🧹 Тоза кардани кеш
     */
    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');

        return back()->with('success', '🧹 Ҳамаи кеш тоза шуд!');
    }

    /**
     * ➕ Сохтани соли нави хониш + 2 семестр автоматӣ
     */
    public function newYear(Request $request): RedirectResponse
    {
        $request->validate(['start_year' => 'required|integer|min:2000|max:2100']);

        $sy   = (int) $request->start_year;
        $name = $sy . '-' . ($sy + 1);

        $year = AcademicYear::firstOrCreate(
            ['name' => $name],
            [
                'start_date' => $sy . '-09-01',
                'end_date'   => ($sy + 1) . '-06-30',
                'status'     => 'planning',
                'is_current' => 0,
                'is_active'  => 1,
            ]
        );

        $semCount = 0;
        $ranges = [
            1 => [$sy . '-09-01', ($sy + 1) . '-01-25'],
            2 => [($sy + 1) . '-02-01', ($sy + 1) . '-06-30'],
        ];

        foreach ($ranges as $n => [$start, $end]) {
            $data = ['academic_year_id' => $year->id, 'name' => 'Семестри ' . $n];
            if (Schema::hasColumn('semesters', 'number'))     $data['number']     = $n;
            if (Schema::hasColumn('semesters', 'start_date')) $data['start_date'] = $start;
            if (Schema::hasColumn('semesters', 'end_date'))   $data['end_date']   = $end;
            if (Schema::hasColumn('semesters', 'is_current')) $data['is_current'] = 0;
            if (Schema::hasColumn('semesters', 'status'))     $data['status']     = 'planning';
            if (Schema::hasColumn('semesters', 'is_active'))  $data['is_active']  = 1;

            $sem = Semester::firstOrCreate(
                ['academic_year_id' => $year->id, 'name' => 'Семестри ' . $n],
                $data
            );

            if ($sem->wasRecentlyCreated) $semCount++;
        }

        return back()->with('success', "✅ Соли хониши {$name} сохта шуд + {$semCount} семестр!");
    }

    /**
     * ⭐ Фаъол кардани сол (НАВСОЗӢ: семестрҳо ҳам ҳамоҳанг мешаванд)
     */
    public function activateYear(Request $request): RedirectResponse
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ]);

        $year = AcademicYear::findOrFail($request->academic_year_id);
        $selectedSemesterId = $request->integer('semester_id') ?: null;

        if ($selectedSemesterId && !Semester::whereKey($selectedSemesterId)
            ->where('academic_year_id', $year->id)
            ->exists()) {
            return back()->withErrors(['semester_id' => 'Семестри интихобшуда ба соли таҳсилии интихобшуда тааллуқ надорад.']);
        }

        DB::transaction(function () use ($year, $selectedSemesterId) {
            AcademicYear::whereKeyNot($year->id)->update(['is_current' => false]);
            AcademicYear::whereKeyNot($year->id)
                ->where('status', 'active')
                ->update(['status' => 'completed']);

            $year->update(['is_current' => true, 'status' => 'active', 'is_active' => true]);

            Semester::query()->update(['is_current' => false]);

            $semesters = $year->semesters()->orderBy('number')->get();

            $current = $selectedSemesterId
                ? $semesters->firstWhere('id', $selectedSemesterId)
                : ($semesters->first(
                    fn ($s) => $s->start_date && $s->end_date
                        && now()->between($s->start_date, $s->end_date)
                ) ?? $semesters->first());

            if ($current) {
                $current->update(['is_current' => true, 'status' => 'active']);
            }
        });

        return back()->with('success', "⭐ Соли {$year->name} ҳамчун соли ҷорӣ фаъол шуд!");
    }
}
