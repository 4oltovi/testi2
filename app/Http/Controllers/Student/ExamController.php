<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Enums\GradeScale;
use App\Models\AnswerOption;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\SemesterGrade;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\DebtDetector;
use App\Services\ExamGradingService;
use App\Services\GradeCalculator;

class ExamController extends Controller
{
    private ExamGradingService $gradingService;

    public function __construct(ExamGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Рӯйхати тестҳои дастрас
     */
    public function index(Request $request): View
    {
        $student = $this->getStudent($request);

        $exams = Exam::where('group_id', $student->group_id)
            ->whereIn('status', ['active', 'scheduled'])
            ->where('is_published', true)
            ->where('format', 'online_test')
            ->with(['subjectAssignment.subject'])
            ->orderBy('starts_at')
            ->get();

        $attempts = ExamAttempt::where('student_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->groupBy('exam_id');

        return view('student.exams.index', compact('exams', 'attempts', 'student'));
    }

    /**
     * Оғоз кардани тест
     */
    public function start(Exam $exam, Request $request): RedirectResponse
    {
        $student = $this->getStudent($request);
        $this->authorizeStudentExam($exam, $student);

        // Санҷиш: тест фаъол аст?
        if ($exam->status !== 'active') {
            return back()->with('error', 'Ин тест ҳоло фаъол нест.');
        }

        // Санҷиш: вақти тест
        if ($exam->starts_at && now()->lt($exam->starts_at)) {
            return back()->with('error', 'Тест ҳанӯз оғоз нашудааст.');
        }
        if ($exam->ends_at && now()->gt($exam->ends_at)) {
            return back()->with('error', 'Вақти тест тамом шудааст.');
        }

        $questionsCount = $exam->examQuestions()->count();
        if ($questionsCount <= 0) {
            return back()->with('error', 'Ин имтиҳон ҳанӯз савол надорад. Ба администратор муроҷиат кунед.');
        }

        // Санҷиш: шумораи кӯшишҳо
        $attemptCount = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptCount >= $exam->max_attempts) {
            return back()->with('error', 'Шумо ба ҳадди аксари кӯшишҳо расидаед.');
        }

        // Санҷиш: кӯшиши нотамом мавҷуд?
        $activeAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeAttempt) {
            return redirect()->route('student.exams.take', [$exam, $activeAttempt]);
        }

        // Сохтани кӯшиши нав
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => $attemptCount + 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity_at' => now(),
        ]);

        return redirect()->route('student.exams.take', [$exam, $attempt]);
    }

    /**
     * Саҳифаи тестсупорӣ (бо таймер)
     */
    public function take(Exam $exam, ExamAttempt $attempt, Request $request): View|RedirectResponse
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id || $attempt->exam_id !== $exam->id) {
            abort(403);
        }

        // Агар аллакай супорида шуда бошад
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.exams.result', [$exam, $attempt]);
        }

        // Саволҳо
        $examQuestions = $exam->examQuestions()
            ->with('question.answerOptions')
            ->orderBy('sort_order')
            ->get();

        if ($examQuestions->isEmpty()) {
            return back()->with('error', 'Ин имтиҳон ҳанӯз савол надорад.');
        }

        // Агар shuffle
        if ($exam->shuffle_questions) {
            $examQuestions = $examQuestions->shuffle();
        }

        // Ҷавобҳои мавҷудаи донишҷӯ
        $existingAnswers = ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->get(['exam_question_id', 'selected_options', 'text_answer'])
            ->mapWithKeys(fn($a) => [
                $a->exam_question_id => $a->selected_options ? json_decode($a->selected_options, true) : ($a->text_answer ?: null),
            ]);

        // Вақти боқимонда (дар сонияҳо)
        $startedAt = $attempt->started_at;
        $totalSeconds = $exam->duration_minutes * 60;

        if ($startedAt) {
            $elapsedSeconds = (int) abs(now()->timestamp - $startedAt->timestamp);
        } else {
            // Агар started_at нест — ҳозир сар кунем
            $attempt->update(['started_at' => now()]);
            $elapsedSeconds = 0;
        }

        $remainingSeconds = (int) max(0, $totalSeconds - $elapsedSeconds);

        // Агар вақт тамом шуда бошад — auto submit
        if ($remainingSeconds <= 0) {
            $this->autoSubmit($attempt, $exam);
            return redirect()->route('student.exams.result', [$exam, $attempt])
                ->with('info', 'Вақти тест тамом шуд.');
        }

        // Танзимоти тест
        $testSettings = [
            'auto_submit' => (bool) Setting::get('test_auto_submit', true),
            'allow_back' => $exam->allow_back_navigation,
        ];

        return view('student.exams.take', compact(
            'exam',
            'attempt',
            'examQuestions',
            'existingAnswers',
            'remainingSeconds',
            'testSettings'
        ));
    }

    /**
     * Сабти ҷавоб (AJAX — auto-save)
     */
    public function saveAnswer(Exam $exam, ExamAttempt $attempt, Request $request): JsonResponse
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id || $attempt->status !== 'in_progress') {
            return response()->json(['error' => 'Дастрасӣ нест'], 403);
        }

        $request->validate([
            'exam_question_id' => 'required|exists:exam_questions,id',
            'selected_options' => 'nullable|array',
            'text_answer' => 'nullable|string',
        ]);

        $examQuestion = ExamQuestion::findOrFail($request->exam_question_id);

        ExamAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'exam_question_id' => $examQuestion->id,
            ],
            [
                'question_id' => $examQuestion->question_id,
                'selected_options' => $request->selected_options ? json_encode($request->selected_options) : null,
                'text_answer' => $request->text_answer,
                'answered_at' => now(),
            ]
        );

        // Last activity
        $attempt->update(['last_activity_at' => now()]);

        return response()->json(['status' => 'saved']);
    }

    /**
     * Супоридани тест (manual submit)
     */
    public function submit(Exam $exam, ExamAttempt $attempt, Request $request): RedirectResponse
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id || $attempt->status !== 'in_progress') {
            return back()->with('error', 'Хатогӣ.');
        }

        if ($exam->ends_at && now()->greaterThan($exam->ends_at)) {
            $this->processSubmission($attempt, $exam, 'auto_submitted');
            return redirect()->route('student.exams.result', [$exam, $attempt])
                ->with('warning', 'Вақти имтиҳон ба охир расид. Натиҷа бо истифода аз ҷавобҳои сабтшуда ҳисоб карда шуд.');
        }

        $this->processSubmission($attempt, $exam, 'submitted');

        return redirect()->route('student.exams.result', [$exam, $attempt])
            ->with('success', 'Тест бо муваффақият супорида шуд!');
    }

    /**
     * Натиҷаи тест
     */
    public function result(Exam $exam, ExamAttempt $attempt, Request $request): View
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        $attempt->load(['examAnswers.question.answerOptions', 'examAnswers.examQuestion']);

        $showDetails = $exam->show_results_immediately;

        return view('student.exams.result', compact('exam', 'attempt', 'showDetails'));
    }

    /**
     * Auto-submit ҳангоми тамом шудани вақт
     */
    private function autoSubmit(ExamAttempt $attempt, Exam $exam): void
    {
        $this->processSubmission($attempt, $exam, 'auto_submitted');
    }

    /**
     * Коркарди супориш (ҳисоби баллҳо)
     */
    private function processSubmission(ExamAttempt $attempt, Exam $exam, string $status): void
    {
        DB::transaction(function () use ($attempt, $exam, $status) {
            $answers = ExamAnswer::where('exam_attempt_id', $attempt->id)->get();
            $examQuestions = ExamQuestion::where('exam_id', $exam->id)
                ->with('question.answerOptions')
                ->get()
                ->keyBy('id');

            $totalScore = 0;
            $maxPossible = 0;

            foreach ($examQuestions as $eq) {
                $question = $eq->question;
                $questionWeight = $this->questionWeight($question);
                $maxPossible += $questionWeight;

                $answer = $answers->where('exam_question_id', $eq->id)->first();
                if (!$answer) continue;

                $result = $this->gradingService->gradeExamAttempt($attempt, $eq, $answer, $questionWeight);

                $answer->update([
                    'is_correct' => $result['is_correct'],
                    'points_earned' => $result['points_earned'],
                    'is_graded' => $result['is_graded'],
                ]);

                $totalScore += $result['points_earned'];
            }

            $percentage = $maxPossible > 0 ? round(($totalScore / $maxPossible) * 100, 2) : 0;
            $grade = GradeScale::fromPercentage($percentage);

            $attempt->update([
                'status' => $status,
                'submitted_at' => $status === 'submitted' ? now() : null,
                'auto_submitted_at' => $status === 'auto_submitted' ? now() : null,
                'total_score' => $totalScore,
                'max_possible_score' => $maxPossible,
                'percentage' => $percentage,
                'letter_grade' => $grade->value,
                'grade_point' => $grade->gradePoint(),
            ]);

            // Навсозии SemesterGrade
            $this->updateSemesterGrade($attempt, $exam, $percentage);
        });
    }

    private function updateSemesterGrade(ExamAttempt $attempt, Exam $exam, float $percentage): void
    {
        $subjectAssignment = $exam->subjectAssignment;
        if (!$subjectAssignment) {
            return;
        }

        $semesterGrade = SemesterGrade::where('student_id', $attempt->student_id)
            ->where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $exam->semester_id)
            ->first();

        if (!$semesterGrade) {
            $semesterGrade = SemesterGrade::create([
                'student_id' => $attempt->student_id,
                'subject_id' => $subjectAssignment->subject_id,
                'subject_assignment_id' => $subjectAssignment->id,
                'semester_id' => $exam->semester_id,
                'status' => 'in_progress',
            ]);
        }

        $semesterGrade->exam_score = $percentage;
        $semesterGrade->save();

        app(GradeCalculator::class)->recalculateAndPersist(
            $attempt->student_id,
            $subjectAssignment->id,
            $exam->semester_id
        );

        if (!$semesterGrade->isPassed()) {
            app(\App\Services\DebtDetector::class)->checkAndCreateDebt($semesterGrade);
        }

        app(\App\Services\DebtDetector::class)->syncDebtsForSubject(
            $subjectAssignment->subject_id,
            $exam->semester_id
        );

        app(\App\Services\DebtDetector::class)->autoFailAbsentStudents(
            $subjectAssignment->subject_id,
            $exam->semester_id,
            $attempt->student_id
        );
    }

    private function questionWeight($question): float
    {
        return match ($question->type ?? '') {
            'single_choice', 'multiple_choice', 'true_false' => 2.5,
            'matching' => 10.0,
            'open_text' => 0.0,
            default => (float) ($question->points ?? 2.5),
        };
    }

    /**
     * Гирифтани донишҷӯи ҷорӣ
     */
    private function getStudent(Request $request): Student
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        return $student;
    }

    /**
     * Санҷиш: тест барои гурӯҳи донишҷӯ?
     */
    private function authorizeStudentExam(Exam $exam, Student $student): void
    {
        if ($exam->group_id !== $student->group_id) {
            abort(403, 'Ин тест барои гурӯҳи шумо нест.');
        }
    }
}