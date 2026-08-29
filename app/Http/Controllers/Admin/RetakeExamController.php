<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\Exam;
use App\Models\Group;
use App\Models\RetakeExam;
use App\Models\RetakeExamStudent;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\DebtDetector;
use App\Services\GradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RetakeExamController extends Controller
{
    private DebtDetector $debtDetector;
    private GradeCalculator $gradeCalculator;

    public function __construct(DebtDetector $debtDetector, GradeCalculator $gradeCalculator)
    {
        $this->debtDetector = $debtDetector;
        $this->gradeCalculator = $gradeCalculator;
    }

    // ===================== РӮЙХАТИ ИМТИҲОНҲОИ ТАКРОРӢ =====================
    public function index(Request $request): View
    {
        $query = RetakeExam::with(['subject', 'semester', 'teacher', 'creator'])
            ->orderByDesc('exam_date');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $retakeExams = $query->paginate(20);
        $subjects = Subject::orderBy('name')->get();
        $currentYear = \App\Models\AcademicYear::current();
        $semesters = Semester::when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->orderByDesc('start_date')
            ->get();

        return view('admin.retake-exams.index', compact(
            'retakeExams',
            'subjects',
            'semesters'
        ));
    }

    // ===================== СОХТАНИ ИМТИҲОНИ ТАКРОРӢ =====================
    public function create(Request $request): View
    {
        $subjects = Subject::whereHas('academicDebts', function ($query) {
            $query->whereIn('status', ['active', 'retake_scheduled']);
        })->orderBy('name')->get();
        $currentYear = \App\Models\AcademicYear::current();
        $semesters = Semester::when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->orderByDesc('start_date')
            ->get();

        $subjectId = $request->get('subject_id');
        $semesterId = $request->get('semester_id');

        $eligibleDebts = collect();

        if ($subjectId && $semesterId) {
            $eligibleDebts = AcademicDebt::with(['student.user', 'student.group', 'subject', 'semester'])
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->whereIn('status', ['active', 'retake_scheduled'])
                ->get()
                ->groupBy('student.group.name');
        }

        return view('admin.retake-exams.create', compact(
            'subjects',
            'semesters',
            'eligibleDebts',
            'subjectId',
            'semesterId'
        ));
    }

    // ===================== САБТИ ИМТИҲОНИ ТАКРОРӢ =====================
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'format' => 'required|in:online_test,written,oral,mixed',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'passing_score' => 'required|numeric|min:0|max:100',
            'max_attempts' => 'required|integer|min:1|max:5',
            'exam_date' => 'required|date',
            'notes' => 'nullable|string',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $mainExam = Exam::where('subject_assignment_id', function ($query) use ($validated) {
                $query->select('id')->from('subject_assignments')
                    ->where('subject_id', $validated['subject_id'])
                    ->where('semester_id', $validated['semester_id'])
                    ->limit(1);
            })
            ->where('exam_type', 'main')
            ->first();

        DB::transaction(function () use ($validated, $request, $mainExam) {
            $retakeExam = RetakeExam::create([
                'subject_id' => $validated['subject_id'],
                'semester_id' => $validated['semester_id'],
                'main_exam_id' => $mainExam?->id,
                'title' => $validated['title'],
                'description' => $request->input('description'),
                'format' => $validated['format'],
                'duration_minutes' => $validated['duration_minutes'],
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'],
                'exam_date' => $validated['exam_date'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id(),
                'status' => 'scheduled',
            ]);

            foreach ($validated['student_ids'] as $studentId) {
                $debt = AcademicDebt::where('student_id', $studentId)
                    ->where('subject_id', $validated['subject_id'])
                    ->where('semester_id', $validated['semester_id'])
                    ->whereIn('status', ['active', 'retake_scheduled'])
                    ->first();

                if (!$debt) {
                    continue;
                }

                $existing = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                    ->where('student_id', $studentId)
                    ->where('academic_debt_id', $debt->id)
                    ->exists();

                if ($existing) {
                    continue;
                }

                RetakeExamStudent::create([
                    'retake_exam_id' => $retakeExam->id,
                    'student_id' => $studentId,
                    'academic_debt_id' => $debt->id,
                    'attempt_number' => ($debt->retake_attempts_used ?? 0) + 1,
                    'status' => 'pending',
                ]);
            }
        });

        return redirect()->route('admin.retake-exams.show', $retakeExam)
            ->with('success', 'Имтиҳони такрорӣ бомуваффақият сохта шуд.');
    }

    // ===================== НАМОИШИ ИМТИҲОНИ ТАКРОРӢ =====================
    public function show(RetakeExam $retakeExam): View
    {
        $retakeExam->load([
            'subject',
            'semester',
            'teacher',
            'creator',
            'retakeExamStudents.student.user',
            'retakeExamStudents.academicDebt',
            'retakeExamStudents.examiner'
        ]);

        return view('admin.retake-exams.show', compact('retakeExam'));
    }

    // ===================== ВОРИДИ НАТИҶА =====================
    public function enterScore(Request $request, RetakeExamStudent $retakeExamStudent): RedirectResponse
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'note' => 'nullable|string',
        ]);

        $retakeExam = $retakeExamStudent->retakeExam;
        $score = (float) $request->input('score');
        $gradeEnum = \App\Enums\GradeScale::fromPercentage($score);
        $isPassing = $gradeEnum->isPassing();

        DB::transaction(function () use ($retakeExamStudent, $score, $gradeEnum, $isPassing, $request) {
            $retakeExamStudent->update([
                'score' => $score,
                'letter_grade' => $gradeEnum->value,
                'status' => $isPassing ? 'passed' : 'failed',
                'examiner_id' => auth()->id(),
                'examined_at' => now(),
                'note' => $request->input('note'),
            ]);

            $debt = $retakeExamStudent->academicDebt;

            if ($isPassing) {
                $debt->resolve(
                    $score,
                    $gradeEnum->value,
                    auth()->id(),
                    'Ҳал шуд аз рӯи имтиҳони такрорӣ'
                );

                $semesterGrade = $debt->semesterGrade;
                if ($semesterGrade) {
                    $semesterGrade->update([
                        'retake_score' => $score,
                        'retake_date' => now(),
                    ]);
                    $this->gradeCalculator->processAndSaveFinalGrade($semesterGrade);
                }
            } else {
                $debt->update([
                    'retake_attempts_used' => DB::raw('retake_attempts_used + 1'),
                ]);

                $remainingAttempts = ($debt->retake_attempts_used ?? 0) + 1;

                if ($remainingAttempts >= $debt->max_retake_attempts) {
                    $debt->update([
                        'status' => 'escalated',
                    ]);
                }
            }
        });

        return back()->with('success', 'Натиҷа сабт шуд: ' . $gradeEnum->value . ' (' . $score . '%)');
    }

    // ===================== ВЕДОМОСТИ ТАКРОРӢ =====================
    public function vedomost(RetakeExam $retakeExam): View
    {
        $retakeExam->load([
            'subject',
            'semester',
            'retakeExamStudents.student.user',
            'retakeExamStudents.academicDebt.semesterGrade',
        ]);

        $rows = $retakeExam->retakeExamStudents
            ->sortBy(fn($r) => mb_strtolower($r->student->user->last_name ?? ''))
            ->values()
            ->map(function ($r, $i) {
                $sg = $r->academicDebt->semesterGrade;
                return [
                    'n' => $i + 1,
                    'student_id' => $r->student->student_id_number ?? $r->student->id,
                    'fio' => $r->student->user?->short_name ?? 'Донишҷӯ #' . $r->student->id,
                    'group_name' => $r->student->group?->name ?? '-',
                    'original_score' => $sg?->total_score ?? '-',
                    'original_grade' => $sg?->letter_grade ?? '-',
                    'retake_score' => $r->score !== null ? number_format($r->score, 2) : '-',
                    'retake_grade' => $r->letter_grade ?? '-',
                    'status' => $r->status,
                    'attempt' => $r->attempt_number,
                ];
            });

        $groupedRows = $rows->groupBy('group_name');

        return view('admin.retake-exams.vedomost', compact(
            'retakeExam',
            'rows',
            'groupedRows'
        ));
    }

    // ===================== ПЕЧАТИ ВЕДОМОСТИ ТАКРОРӢ =====================
    public function printVedomost(RetakeExam $retakeExam)
    {
        $retakeExam->load([
            'subject',
            'semester',
            'retakeExamStudents.student.user',
            'retakeExamStudents.academicDebt.semesterGrade',
        ]);

        $rows = $retakeExam->retakeExamStudents
            ->sortBy(fn($r) => mb_strtolower($r->student->user->last_name ?? ''))
            ->values()
            ->map(function ($r, $i) {
                $sg = $r->academicDebt->semesterGrade;
                return [
                    'n' => $i + 1,
                    'student_id' => $r->student->student_id_number ?? $r->student->id,
                    'fio' => $r->student->user?->short_name ?? 'Донишҷӯ #' . $r->student->id,
                    'group_name' => $r->student->group?->name ?? '-',
                    'original_score' => $sg?->total_score ?? '-',
                    'original_grade' => $sg?->letter_grade ?? '-',
                    'retake_score' => $r->score !== null ? number_format($r->score, 2) : '-',
                    'retake_grade' => $r->letter_grade ?? '-',
                    'status' => $r->status,
                    'attempt' => $r->attempt_number,
                ];
            });

        $groupedRows = $rows->groupBy('group_name');

        $data = [
            'exam' => $retakeExam,
            'groupedRows' => $groupedRows,
            'rows' => $rows,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.retake-exams.vedomost-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        $name = 'retake_vedomost_' . $retakeExam->id . '.pdf';

        return $pdf->download($name);
    }
}
