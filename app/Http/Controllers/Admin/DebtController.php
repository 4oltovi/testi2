<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\DebtStatus;
use App\Models\AcademicDebt;
use App\Models\Group;
use App\Models\Semester;
use App\Services\DebtDetector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DebtController extends Controller
{
    private DebtDetector $debtDetector;

    public function __construct(DebtDetector $debtDetector)
    {
        $this->debtDetector = $debtDetector;
    }

    public function index(Request $request): View
    {
        $query = AcademicDebt::with(['student.user', 'student.group', 'subject', 'semester']);

        // Филтр
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        } else {
            $query->open(); // Ба таври пешфарз — танҳо кушодҳо
        }

        if ($groupId = $request->get('group_id')) {
            $query->whereHas('student', fn($q) => $q->where('group_id', $groupId));
        }

        if ($semesterId = $request->get('semester_id')) {
            $query->where('semester_id', $semesterId);
        }

        if ($search = $request->get('search')) {
            $query->whereHas('student.user', fn($q) =>
                $q->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
            );
        }

        $debts = $query->orderByDesc('debt_date')->paginate(25)->withQueryString();

        $groups = Group::active()->orderBy('name')->get();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        // Омор
        $stats = [
            'total_open' => AcademicDebt::open()->count(),
            'active' => AcademicDebt::where('status', DebtStatus::ACTIVE)->count(),
            'retake_scheduled' => AcademicDebt::where('status', DebtStatus::RETAKE_SCHEDULED)->count(),
            'overdue' => AcademicDebt::overdue()->count(),
            'resolved_this_month' => AcademicDebt::where('status', DebtStatus::RESOLVED)
                ->where('resolved_date', '>=', now()->startOfMonth())->count(),
        ];

        return view('admin.debts.index', compact('debts', 'groups', 'semesters', 'stats'));
    }

    public function show(AcademicDebt $debt): View
    {
        $debt->load(['student.user', 'student.group', 'subject', 'semester', 'curriculum', 'history.performedBy', 'semesterGrade']);
        return view('admin.debts.show', compact('debt'));
    }

    public function scheduleRetake(AcademicDebt $debt, Request $request): RedirectResponse
    {
        $request->validate([
            'retake_date' => 'nullable|date|after:today',
        ]);

        if (!$debt->canRetake()) {
            return back()->with('error', 'Ин донишҷӯ имкони такрорсупорӣ надорад.');
        }

        $retakeDate = $request->input('retake_date') ? new \DateTime($request->input('retake_date')) : null;
        $this->debtDetector->scheduleRetake($debt, $retakeDate);

        return back()->with('success', 'Такрорсупорӣ таъин шуд.');
    }

    public function resolve(AcademicDebt $debt, Request $request): RedirectResponse
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'note' => 'nullable|string|max:500',
        ], [
            'score.required' => 'Баҳо ҳатмӣ аст.',
        ]);

        $score = $request->input('score');
        $grade = \App\Enums\GradeScale::fromPercentage($score);

        if (!$grade->isPassing()) {
            // Агар боз ҳам нагузашт — кӯшишро зиёд кун
            $debt->increment('retake_attempts_used');

            if ($debt->retake_attempts_used >= $debt->max_retake_attempts) {
                $this->debtDetector->escalateToCommission($debt);
                return back()->with('warning', "Донишҷӯ боз нагузашт ({$grade->value}). Ба комиссия фиристода шуд.");
            }

            return back()->with('warning', "Донишҷӯ нагузашт ({$grade->value}, {$score}%). Кӯшиши {$debt->retake_attempts_used}/{$debt->max_retake_attempts}.");
        }

        // Гузашт — ҳал кардан
        $debt->resolve($score, $grade->value, auth()->id(), $request->input('note'));

        // Навсозии semester_grade
        if ($debt->semesterGrade) {
            $debt->semesterGrade->update([
                'retake_score' => $score,
                'retake_date' => now(),
            ]);
            // Аз нав ҳисоб
            $gradeCalc = app(\App\Services\GradeCalculator::class);
            $gradeCalc->processAndSaveFinalGrade($debt->semesterGrade);
        }

        return back()->with('success', "Қарздорӣ ҳал шуд! Баҳо: {$grade->value} ({$score}%)");
    }

    public function escalate(AcademicDebt $debt): RedirectResponse
    {
        $this->debtDetector->escalateToCommission($debt);
        return back()->with('success', 'Ба комиссияи такрорсупорӣ фиристода шуд.');
    }
}
