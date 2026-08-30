<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\Exam;
use App\Models\Group;
use App\Models\RetakeExam;
use App\Models\RetakeExamAnswer;
use App\Models\RetakeExamAttempt;
use App\Models\RetakeExamQuestion;
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
                $query->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                    ->where('retake_allowed', true);
            })
            ->orderBy('name')
            ->get();

        $currentYear = \App\Models\AcademicYear::current();
        $semesters = Semester::when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->orderByDesc('start_date')
            ->get();

        $subjectId = $request->get('subject_id');
        $semesterId = $request->get('semester_id');

        return view('admin.retake-exams.create', compact(
            'subjects',
            'semesters',
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
            'exam_date' => 'required|date',
        ]);

        $mainExam = Exam::where('subject_assignment_id', function ($query) use ($validated) {
                $query->select('id')->from('subject_assignments')
                    ->where('subject_id', $validated['subject_id'])
                    ->where('semester_id', $validated['semester_id'])
                    ->limit(1);
            })
            ->where('exam_type', 'main')
            ->first();

        if (!$mainExam) {
            return back()->with('error', 'Барои ин фан ва семестр имтиҳони асосӣ ёфт нашуд. Аввал имтиҳони асосиро созед.');
        }

        $questionCount = $mainExam->examQuestions()->count();
        if ($questionCount <= 0) {
            return back()->with('error', 'Барои имтиҳони асосӣ саволнома омода нашудааст. Аввал саволномаро анҷом диҳед.');
        }

        $hasEligibleDebt = AcademicDebt::where('subject_id', $validated['subject_id'])
            ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
            ->where('retake_allowed', true)
            ->exists();

        if (!$hasEligibleDebt) {
            return back()->with('error', 'Барои ин фан қарздории фаъол вуҷуд надорад. Имтиҳони такрорӣ танҳо барои фанҳои қарздор эҷод карда мешавад.');
        }

        $retakeExam = DB::transaction(function () use ($validated, $mainExam, $questionCount) {
            $retakeExam = RetakeExam::create([
                'subject_id' => $validated['subject_id'],
                'semester_id' => $validated['semester_id'],
                'main_exam_id' => $mainExam->id,
                'title' => 'Имтиҳони такрорӣ — ' . ($mainExam->subjectAssignment->subject->name ?? 'Фан'),
                'description' => $mainExam->description ?? 'Имтиҳони такрорӣ',
                'format' => $mainExam->format,
                'duration_minutes' => $mainExam->duration_minutes,
                'passing_score' => $mainExam->passing_score,
                'max_attempts' => $mainExam->max_attempts,
                'exam_date' => $validated['exam_date'],
                'notes' => null,
                'created_by' => auth()->id(),
                'status' => 'scheduled',
            ]);

            $examQuestions = $mainExam->examQuestions()->orderBy('sort_order')->get();
            foreach ($examQuestions as $eq) {
                RetakeExamQuestion::create([
                    'retake_exam_id' => $retakeExam->id,
                    'question_id' => $eq->question_id,
                    'sort_order' => $eq->sort_order,
                    'points' => $eq->points,
                ]);
            }

            $eligibleDebts = AcademicDebt::where('subject_id', $validated['subject_id'])
                ->where('semester_id', $validated['semester_id'])
                ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->where('retake_allowed', true)
                ->get();

            foreach ($eligibleDebts as $debt) {
                RetakeExamStudent::create([
                    'retake_exam_id' => $retakeExam->id,
                    'student_id' => $debt->student_id,
                    'academic_debt_id' => $debt->id,
                    'attempt_number' => ($debt->retake_attempts_used ?? 0) + 1,
                    'status' => 'pending',
                ]);
            }

            return $retakeExam;
        });

        return redirect()->route('admin.retake-exams.show', $retakeExam)
            ->with('success', "Имтиҳони такрорӣ бомуваффақият сохта шуд. {$questionCount} савол аз имтиҳони асосӣ копи карда шуд.");
    }

    public function checkMainExam(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $mainExam = Exam::where('subject_assignment_id', function ($query) use ($request) {
                $query->select('id')->from('subject_assignments')
                    ->where('subject_id', $request->subject_id)
                    ->where('semester_id', $request->semester_id)
                    ->limit(1);
            })
            ->where('exam_type', 'main')
            ->first();

        if (!$mainExam) {
            return response()->json([
                'exists' => false,
                'message' => 'Барои ин фан ва семестр имтиҳони асосӣ ёфт нашуд. Аввал имтиҳони асосиро созед.',
            ]);
        }

        $questionCount = $mainExam->examQuestions()->count();
        $eligibleDebts = AcademicDebt::where('subject_id', $request->subject_id)
            ->where('semester_id', $request->semester_id)
            ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
            ->where('retake_allowed', true)
            ->count();

        if ($eligibleDebts <= 0) {
            return response()->json([
                'exists' => false,
                'message' => 'Барои ин фан ва семестр қарздории фаъол вуҷуд надорад. Имтиҳони такрорӣ танҳо барои фанҳои қарздор эҷод карда мешавад.',
            ]);
        }

        return response()->json([
            'exists' => true,
            'format' => $mainExam->format,
            'duration_minutes' => $mainExam->duration_minutes,
            'passing_score' => $mainExam->passing_score,
            'questions_count' => $questionCount,
            'eligible_debtors' => $eligibleDebts,
        ]);
    }

    // ===================== НАМОИШИ ИМТИҲОНИ ТАКРОРӢ =====================
    public function show(RetakeExam $retakeExam): View
    {
        $retakeExam->load([
            'subject',
            'semester',
            'teacher',
            'creator',
            'mainExam',
            'mainExam.examQuestions.question',
            'retakeExamStudents.student.user',
            'retakeExamStudents.academicDebt.semesterGrade',
            'retakeExamStudents.academicDebt',
        ]);

        $attempts = RetakeExamAttempt::where('retake_exam_id', $retakeExam->id)
            ->with('student.user')
            ->get()
            ->groupBy('student_id');

        return view('admin.retake-exams.show', compact('retakeExam', 'attempts'));
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
                $attempt = \App\Models\RetakeExamAttempt::where('retake_exam_id', $r->retake_exam_id)
                    ->where('student_id', $r->student_id)
                    ->where('retake_exam_student_id', $r->id)
                    ->orderByDesc('attempt_number')
                    ->first();

                return [
                    'n' => $i + 1,
                    'student_id' => $r->student->student_id_number ?? $r->student->id,
                    'fio' => $r->student->user?->short_name ?? 'Донишҷӯ #' . $r->student->id,
                    'group_name' => $r->student->group?->name ?? '-',
                    'original_score' => $sg?->total_score ?? '-',
                    'original_grade' => $sg?->letter_grade ?? '-',
                    'retake_score' => $attempt?->percentage !== null ? number_format($attempt->percentage, 2) : '-',
                    'retake_grade' => $attempt?->letter_grade ?? '-',
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
                $attempt = \App\Models\RetakeExamAttempt::where('retake_exam_id', $r->retake_exam_id)
                    ->where('student_id', $r->student_id)
                    ->where('retake_exam_student_id', $r->id)
                    ->orderByDesc('attempt_number')
                    ->first();

                return [
                    'n' => $i + 1,
                    'student_id' => $r->student->student_id_number ?? $r->student->id,
                    'fio' => $r->student->user?->short_name ?? 'Донишҷӯ #' . $r->student->id,
                    'group_name' => $r->student->group?->name ?? '-',
                    'original_score' => $sg?->total_score ?? '-',
                    'original_grade' => $sg?->letter_grade ?? '-',
                    'retake_score' => $attempt?->percentage !== null ? number_format($attempt->percentage, 2) : '-',
                    'retake_grade' => $attempt?->letter_grade ?? '-',
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

    public function destroy(RetakeExam $retakeExam): RedirectResponse
    {
        $retakeExam->delete();

        return redirect()->route('admin.retake-exams.index')
            ->with('success', 'Имтиҳони такрорӣ нест карда шуд.');
    }
}
