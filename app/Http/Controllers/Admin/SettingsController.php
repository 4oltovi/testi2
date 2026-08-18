<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Group;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
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

        return view('admin.settings.index', compact(
            'formulaSettings',
            'testSettings',
            'organizationSettings',
            'securitySettings',
            'academicYears'
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
        $request->validate(['academic_year_id' => 'required|exists:academic_years,id']);

        $year = AcademicYear::findOrFail($request->academic_year_id);

        DB::transaction(function () use ($year) {
            // 1) Солҳои дигар: ғайриҷорӣ (фаъолҳо → анҷомёфта)
            AcademicYear::where('id', '!=', $year->id)->update(['is_current' => 0]);
            AcademicYear::where('id', '!=', $year->id)
                ->where('status', 'active')
                ->update(['status' => 'completed']);

            // 2) Соли интихобшуда: ҷорӣ ва фаъол
            $year->update(['is_current' => 1, 'status' => 'active', 'is_active' => 1]);

            // 3) Ҳамаи семестрҳо: ғайриҷорӣ (ин ислоҳи асосӣ аст!)
            Semester::query()->update(['is_current' => 0]);

            // 4) Семестри ҷорӣ: агар имрӯз дар байни санаҳо бошад — ҳамон,
            //    вагарна семестри аввали ҳамон сол
            $semesters = $year->semesters()->orderBy('number')->get();

            $current = $semesters->first(
                fn ($s) => $s->start_date && $s->end_date
                    && now()->between($s->start_date, $s->end_date)
            ) ?? $semesters->first();

            if ($current) {
                $current->update(['is_current' => 1, 'status' => 'active']);
            }
        });

        return back()->with('success', "⭐ Соли {$year->name} ҳамчун соли ҷорӣ фаъол шуд!");
    }

    /**
     * 🎓 Гузариш ба соли нав — ҳамаи донишҷӯён
     */
    public function promoteAll(): RedirectResponse
    {
        $promoted = 0;
        $graduated = 0;
        $skipped = 0;

        Student::where('status', 'active')->with(['course', 'group'])->get()
            ->each(function ($s) use (&$promoted, &$graduated, &$skipped) {
                $num = (int) ($s->course->number ?? preg_replace('/\D/', '', $s->course->name ?? '') ?: 0);

                if ($num <= 0) {
                    $skipped++;
                    return;
                }

                // Курси охирин → хатмкарда
                if ($num >= 4) {
                    $s->update(['status' => 'graduated']);
                    $graduated++;
                    return;
                }

                $next = Course::where('number', $num + 1)->first()
                    ?? Course::where('name', 'like', '%' . ($num + 1) . '%')->first();

                if (!$next) {
                    $skipped++;
                    return;
                }

                $update = ['course_id' => $next->id];

                if (Schema::hasColumn('groups', 'course_id') && $s->specialty_id) {
                    $nextGroup = Group::where('specialty_id', $s->specialty_id)
                        ->where('course_id', $next->id)
                        ->first();
                    if ($nextGroup) $update['group_id'] = $nextGroup->id;
                }

                $s->update($update);
                $promoted++;
            });

        return back()->with(
            'success',
            "🎓 Гузариш ба соли нав: {$promoted} гузашт, {$graduated} хатм карданд, {$skipped} гузашта нашуданд."
        );
    }
}