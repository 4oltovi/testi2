<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\RatingSession;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\RatingSubjectPairService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RatingSessionController extends Controller
{
    public function __construct(private RatingSubjectPairService $pairService)
    {
    }

    public function index(): View
    {
        $sessions = RatingSession::with('semester.academicYear')
            ->withCount('attempts')
            ->orderByDesc('start_at')
            ->paginate(15);

        return view('admin.rating-sessions.index', compact('sessions'));
    }

    public function create(): View
    {
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();
        $currentSemester = Semester::current();
        $currentSemesterId = $currentSemester?->id;

        // Ҳамаи фанҳои фаъол + шумораи гурӯҳҳое, ки онҳоро дар семестр мехӯҷанд
        $subjects = $this->pairService->subjectsWithGroupCounts($currentSemesterId);

        $groups = Group::active()->with('specialty')->orderBy('name')->get();

        return view('admin.rating-sessions.create', compact('semesters', 'subjects', 'groups', 'currentSemesterId'));
    }

    public function subjectsBySemester(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'integer|exists:groups,id',
        ]);

        $semesterId = (int) $validated['semester_id'];
        $groupIds = $request->input('group_ids', []);

        if (! is_array($groupIds)) {
            $groupIds = [];
        }

        $subjects = $this->pairService->subjectsWithGroupCounts($semesterId, array_map('intval', $groupIds));

        return response()->json([
            'subjects' => $subjects->map(function (Subject $subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'groups_count' => (int) $subject->groups_count,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'period' => 'required|in:rating1,rating2',
            'semester_id' => 'required|exists:semesters,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'duration_minutes' => 'required|integer|min:5|max:180',
            'questions_count' => 'required|integer|min:5|max:100',
            'max_attempts' => 'required|integer|min:1|max:5',
            'schedule_mode' => 'required|in:all,by_group',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
            'group_windows' => 'nullable|array',
        ]);

        $semesterId = (int) $validated['semester_id'];

        // Гурӯҳҳои иштирок: дар ҳолати «Аз рӯи гурӯҳҳо» — ани онҳое,
        // ки равзанаи вақташон дар формул ҳаст; дар ҳолати умумӣ — ҳама.
        $groupIds = [];
        $groupWindows = [];

        if ($validated['schedule_mode'] === 'by_group' && $request->filled('group_windows')) {
            $groupWindows = $request->input('group_windows', []);
            $requested = array_map('intval', array_keys($groupWindows));

            $groupIds = Group::whereIn('id', $requested)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $submittedSubjectIds = collect($validated['subject_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Ҷуфтҳои (гурӯҳ, фан)-и мувофиқ — танҳо барои гурӯҳҳое,
        // ки воқеан ин фанро дар ин семестр мехӯҷанд.
        $pairs = $this->pairService->pairs($semesterId, $submittedSubjectIds, $groupIds);

        $validSubjectIds = collect($pairs)
            ->pluck('subject_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Фанҳое, ки ягон гурӯҳ онҳоро намехӯҷад — гузашта мешаванд
        $skippedSubjectIds = array_values(array_diff($submittedSubjectIds, $validSubjectIds));

        if (empty($validSubjectIds)) {
            return back()->withInput()->withErrors([
                'subject_ids' => 'Ин фанҳоро ягон гурӯҳ дар ин семестр намехӯҷад — сессия сохта нашуд.',
            ]);
        }

        $session = new RatingSession();

        // forceFill — новобаста аз $fillable ҲАМА чизро сабт мекунад
        $session->forceFill([
            'name'             => $validated['name'],
            'period'           => $validated['period'],
            'semester_id'      => $validated['semester_id'],
            'start_at'         => $validated['start_at'],
            'end_at'           => $validated['end_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'questions_count'  => $validated['questions_count'],
            'max_attempts'     => $validated['max_attempts'],
            'schedule_mode'    => $validated['schedule_mode'],
            'status'           => 'draft',
            'created_by'       => auth()->id(),
        ]);

        $session->save();

        // Фанҳо — ЯКБОРА (на як-як!), танҳо фанҳои дарозшаванда
        $session->subjects()->attach(
            collect($validSubjectIds)
                ->mapWithKeys(fn($id) => [$id => ['questions_count' => null]])
                ->all()
        );

        // Гурӯҳҳо бо равзанаи вақт (режими by_group)
        if ($validated['schedule_mode'] === 'by_group' && ! empty($groupWindows)) {
            foreach ($groupIds as $groupId) {
                $w = $groupWindows[$groupId] ?? [];

                $session->groups()->attach($groupId, [
                    'start_at' => ($w['start_at'] ?? null) ?: null,
                    'end_at'   => ($w['end_at'] ?? null) ?: null,
                ]);
            }
        }

        // Хулоса: чанд ҷуфт (гурӯҳ, фан) сохта шуд + кадом фанҳо гузашта шуданд
        $message = sprintf(
            'Сессия сохта шуд: %d ҷуфт (гурӯҳ, фан). Санҷиши омодагиро бинед ва нашр кунед.',
            count($pairs)
        );

        if (! empty($skippedSubjectIds)) {
            $skippedNames = Subject::whereIn('id', $skippedSubjectIds)->pluck('name')->implode(', ');
            $message .= ' Фанҳои бе гурӯҳ (гузошта шуданд): ' . $skippedNames . '.';
        }

        return redirect()->route('admin.rating-sessions.show', $session)
            ->with('success', $message);
    }

    public function show(RatingSession $session): View
    {
        $session->load(['semester.academicYear', 'subjects', 'groups']);

        $readiness = $session->readinessReport();
        $results = $this->buildResults($session);

        return view('admin.rating-sessions.show', compact('session', 'readiness', 'results'));
    }

    public function publish(RatingSession $session): RedirectResponse
    {
        $readiness = $session->readinessReport();

        if ($readiness['missing'] > 0) {
            return back()->with(
                'error',
                "Нашр имкон нест: {$readiness['missing']} фан саволҳои кофӣ надоранд (ҳадди ақал {$session->questions_count} савол лозим)."
            );
        }

        $session->update(['status' => 'active']);

        return back()->with('success', '✅ Рейтинг нашр шуд — донишҷӯён метавонанд супоранд.');
    }

    public function close(RatingSession $session): RedirectResponse
    {
        $session->update(['status' => 'completed']);

        // Кӯшишҳои кушударо автоматӣ пӯшидан
        $session->attempts()->where('status', 'in_progress')->update(['status' => 'auto_closed']);

        return back()->with('success', 'Рейтинг пӯшида шуд 🔒');
    }

    public function extend(Request $request, RatingSession $session): RedirectResponse
    {
        $request->validate(['hours' => 'required|integer|min:1|max:168']);

        $session->update(['end_at' => $session->end_at->addHours((int) $request->hours)]);

        return back()->with('success', "Мӯҳлат {$request->hours} соат дароз шуд.");
    }

    public function destroy(RatingSession $session): RedirectResponse
    {
        if ($session->status !== 'draft') {
            return back()->with('error', 'Танҳо сессияи на нашршударо нест кардан мумкин аст.');
        }

        $session->subjects()->detach();
        $session->groups()->detach();
        $session->delete();

        return redirect()->route('admin.rating-sessions.index')->with('success', 'Сессия нест шуд.');
    }

    /**
     * Протоколи PDF
     */
    public function protocol(RatingSession $session)
    {
        $session->load(['semester.academicYear', 'subjects']);
        $results = $this->buildResults($session);

        $pdf = Pdf::loadView('admin.rating-sessions.protocol', compact('session', 'results'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('protocol-' . str_replace(' ', '-', $session->name) . '.pdf');
    }

    // ================================================================
    // Матритсаи натиҷаҳо — ЯК query барои ҳама (суръатнок)
    // ================================================================
    private function buildResults(RatingSession $session): array
    {
        $journalMax = (float) Setting::get('journal_part_points', 60);
        $testMax = max(0, 100 - $journalMax);

        // Беҳтарин кӯшиш барои ҳар донишҷӯ+фан — ягон N+1 нест
        $bestMap = collect(DB::table('rating_attempts')
            ->where('rating_session_id', $session->id)
            ->where('status', 'finished')
            ->groupBy('student_id', 'subject_id')
            ->selectRaw('student_id, subject_id, MAX(percentage) AS best_pct, COUNT(*) AS attempts_used')
            ->get())
            ->keyBy(fn($r) => $r->student_id . '_' . $r->subject_id);

        $students = Student::where('status', 'active')
            ->with(['user', 'group'])
            ->when(
                $session->schedule_mode === 'by_group',
                fn($q) => $q->whereIn('group_id', $session->groups->pluck('id'))
            )
            ->get()
            ->sortBy(fn($s) => mb_strtolower($s->user?->last_name ?? ''));

        // Фанҳои ҳар донишҷӯ — танҳо онҳое, ки гурӯҳи ӯ воқеан
        // дар семестри сессия мехӯҷад (ҳеҷ сатри холи корӣ намешавад).
        $allowedByGroup = $this->allowedSubjectsByGroup($session);

        $rows = [];
        foreach ($students as $student) {
            $studentSubjectIds = $allowedByGroup->get((int) $student->group_id, []);

            foreach ($session->subjects as $subject) {
                if (! in_array((int) $subject->id, $studentSubjectIds, true)) {
                    continue;
                }

                $r = $bestMap->get($student->id . '_' . $subject->id);

                $rows[] = [
                    'student' => $student->user?->full_name ?? '-',
                    'group' => $student->group?->display_name ?? '-',
                    'subject' => $subject->name,
                    'pct' => $r ? round((float) $r->best_pct, 2) : null,
                    'scaled' => $r ? round(((float) $r->best_pct / 100) * $testMax, 2) : null,
                    'attempts' => $r?->attempts_used,
                ];
            }
        }

        return [
            'rows' => $rows,
            'journal_max' => $journalMax,
            'test_max' => $testMax,
        ];
    }

    /**
     * [group_id => [subject_id, ...]] — фанҳои ҳар гурӯҳ дар семестри сессия.
     */
    private function allowedSubjectsByGroup(RatingSession $session): \Illuminate\Support\Collection
    {
        $groupIds = $session->schedule_mode === 'by_group'
            ? $session->groups->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $subjectIds = $session->subjects->pluck('id')->map(fn ($id) => (int) $id)->all();

        return collect($this->pairService->pairs($session->semester_id, $subjectIds, $groupIds))
            ->groupBy('group_id')
            ->map(fn ($items) => $items->pluck('subject_id')->map(fn ($id) => (int) $id)->values()->all());
    }
}
