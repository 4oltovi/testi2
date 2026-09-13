<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\RetakeExam;
use App\Models\RetakeExamAnswer;
use App\Models\RetakeExamAttempt;
use App\Models\RetakeExamStudent;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\ExamQuestion;
use App\Services\DebtDetector;
use App\Services\ExamGradingService;
use App\Services\GradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RetakeExamController extends Controller
{
    private ExamGradingService $gradingService;
    private DebtDetector $debtDetector;

    public function __construct(ExamGradingService $gradingService, DebtDetector $debtDetector)
    {
        $this->gradingService = $gradingService;
        $this->debtDetector = $debtDetector;
    }

    public function index(Request $request): View
    {
        $student = $this->getStudent($request);

        $retakeExams = RetakeExam::whereHas('retakeExamStudents', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->whereIn('status', ['scheduled', 'active'])
            ->with(['subject', 'semester'])
            ->orderByDesc('exam_date')
            ->get();

        $attempts = RetakeExamAttempt::where('student_id', $student->id)
            ->whereIn('retake_exam_id', $retakeExams->pluck('id'))
            ->get()
            ->groupBy('retake_exam_id');

        return view('student.retake-exams.index', compact('retakeExams', 'attempts', 'student'));
    }

    public function start(RetakeExam $retakeExam, Request $request): RedirectResponse
    {
        $student = $this->getStudent($request);
        $this->authorizeStudentRetakeExam($retakeExam, $student);

        \Log::info('RetakeExam start attempt', [
            'retake_exam_id' => $retakeExam->id,
            'status' => $retakeExam->status,
            'exam_date' => $retakeExam->exam_date,
            'now' => now(),
            'student_id' => $student->id,
        ]);

        if ($retakeExam->status === 'draft') {
            \Log::info('RetakeExam start blocked: draft');
            return back()->with('error', 'Ин имтиҳон ҳанӯз нашр нашудааст.');
        }

        if ($retakeExam->status === 'cancelled') {
            \Log::info('RetakeExam start blocked: cancelled');
            return back()->with('error', 'Ин имтиҳон бекор карда шудааст.');
        }

        if ($retakeExam->status !== 'scheduled' && $retakeExam->status !== 'active') {
            \Log::info('RetakeExam start blocked by status', ['status' => $retakeExam->status]);
            return back()->with('error', 'Ин имтиҳони такрорӣ ҳоло дастрас нест.');
        }

        if ($retakeExam->exam_date && $retakeExam->exam_date->isFuture()) {
            \Log::info('RetakeExam start blocked by date', [
                'exam_date' => $retakeExam->exam_date,
                'now' => now(),
            ]);
            return back()->with('error', 'Имтиҳон соати ' . $retakeExam->exam_date->format('d.m.Y') . ' оғоз мешавад.');
        }

        $retakeExamStudent = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->first();

        if (!$retakeExamStudent) {
            return back()->with('error', 'Шумо ба ин имтиҳон такрорӣ даъват нашудаед.');
        }

        $attemptCount = RetakeExamAttempt::where('retake_exam_id', $retakeExam->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptCount >= $retakeExam->max_attempts) {
            return back()->with('error', 'Шумо ба ҳадди аксари кӯшишҳо расидаед.');
        }

        $activeAttempt = RetakeExamAttempt::where('retake_exam_id', $retakeExam->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeAttempt) {
            return redirect()->route('student.retake-exams.take', [$retakeExam, $activeAttempt]);
        }

        $attempt = RetakeExamAttempt::create([
            'retake_exam_id' => $retakeExam->id,
            'student_id' => $student->id,
            'retake_exam_student_id' => $retakeExamStudent->id,
            'attempt_number' => $attemptCount + 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity_at' => now(),
        ]);

        \Log::info('RetakeExam started', [
            'retake_exam_id' => $retakeExam->id,
            'student_id' => $student->id,
            'attempt_id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
        ]);

        return redirect()->route('student.retake-exams.take', [$retakeExam, $attempt]);
    }

    public function take(RetakeExam $retakeExam, RetakeExamAttempt $attempt, Request $request): View|RedirectResponse
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id || $attempt->retake_exam_id !== $retakeExam->id) {
            abort(403);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.retake-exams.result', [$retakeExam, $attempt]);
        }

        $mainExam = $retakeExam->mainExam;

        $examQuestions = $mainExam->examQuestions()
            ->with('question.answerOptions')
            ->orderBy('sort_order')
            ->get();

        if ($examQuestions->isEmpty()) {
            return back()->with('error', 'Ин имтиҳон ҳанӯз савол надорад.');
        }

        if ($mainExam->shuffle_questions) {
            $examQuestions = $examQuestions->shuffle();
        }

        $existingAnswers = RetakeExamAnswer::where('retake_exam_attempt_id', $attempt->id)
            ->get(['exam_question_id', 'selected_options', 'text_answer'])
            ->mapWithKeys(fn($a) => [
                $a->exam_question_id => $a->selected_options ? json_decode($a->selected_options, true) : ($a->text_answer ?: null),
            ]);

        // Вақти боқимондаро аз оғози attempt ҳисоб кунед
        $startedAt = $attempt->started_at ?? now();
        $hasTimeLimit = !empty($retakeExam->duration_minutes);
        $totalSeconds = $retakeExam->duration_minutes * 60;
        $elapsedSeconds = (int) abs(now()->timestamp - $startedAt->timestamp);
        $remainingSeconds = $hasTimeLimit ? (int) max(0, $totalSeconds - $elapsedSeconds) : null;

        // Агар вақт тамом шуда бошад — авто-супоридан (ҳамон механизми Main Exam)
        if ($remainingSeconds !== null && $remainingSeconds <= 0) {
            $this->processSubmission($attempt, $retakeExam, 'auto_submitted');
            return redirect()->route('student.retake-exams.result', [$retakeExam, $attempt])
                ->with('info', 'Вақти имтиҳон ба охир расид.');
        }

        $retakeSaveUrl = route('student.retake-exams.save-answer', [$retakeExam, $attempt]);
        $retakeSubmitUrl = route('student.retake-exams.submit', [$retakeExam, $attempt]);
        $retakeResultUrl = route('student.retake-exams.result', [$retakeExam, $attempt]);

        \Log::info('RetakeExam take view data', [
            'retake_exam_id' => $retakeExam->id,
            'attempt_id' => $attempt->id,
            'retakeSaveUrl' => $retakeSaveUrl,
            'retakeSubmitUrl' => $retakeSubmitUrl,
            'retakeResultUrl' => $retakeResultUrl,
            'isRetakeMode' => true,
            'main_exam_id' => $mainExam->id,
            'questions_count' => $examQuestions->count(),
        ]);

        return view('student.exams.take', [
            'exam' => $mainExam,
            'attempt' => $attempt,
            'examQuestions' => $examQuestions,
            'existingAnswers' => $existingAnswers,
            'remainingSeconds' => $remainingSeconds,
            'hasTimeLimit' => $hasTimeLimit,
            'isRetakeMode' => true,
            'retakeExam' => $retakeExam,
            'retakeSaveUrl' => $retakeSaveUrl,
            'retakeSubmitUrl' => $retakeSubmitUrl,
            'retakeResultUrl' => $retakeResultUrl,
        ]);
    }

    public function saveAnswer(Request $request, RetakeExam $retakeExam, RetakeExamAttempt $attempt): \Illuminate\Http\JsonResponse
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id || $attempt->retake_exam_id !== $retakeExam->id) {
            abort(403);
        }

        if ($attempt->status !== 'in_progress') {
            return response()->json(['status' => 'error', 'message' => 'Attempt is not in progress'], 422);
        }

        $request->validate([
            'exam_question_id' => 'required|exists:exam_questions,id',
            'selected_options' => 'nullable|array',
            'text_answer' => 'nullable|string',
        ]);

        $examQuestion = ExamQuestion::findOrFail($request->exam_question_id);
        $questionType = $examQuestion->question->type ?? '';
        
        \Log::info('RetakeExam saveAnswer called', [
            'retake_exam_id' => $retakeExam->id,
            'attempt_id' => $attempt->id,
            'exam_question_id' => $examQuestion->id,
            'question_type' => $questionType,
            'selected_options_raw' => $request->selected_options,
            'text_answer_raw' => $request->text_answer,
            'all_request' => $request->all(),
        ]);
        
        $selectedOptions = null;
        $textAnswer = null;
        
        if (in_array($questionType, ['single_choice', 'true_false', 'multiple_choice'])) {
            $selectedOptions = $request->selected_options ? json_encode(array_values($request->selected_options)) : null;
        } else {
            $textAnswer = $request->text_answer;
            if (is_array($textAnswer)) {
                $textAnswer = json_encode(array_values($textAnswer));
            }
        }

        RetakeExamAnswer::updateOrCreate(
            [
                'retake_exam_attempt_id' => $attempt->id,
                'exam_question_id' => $examQuestion->id,
            ],
            [
                'question_id' => $examQuestion->question_id,
                'selected_options' => $selectedOptions,
                'text_answer' => $textAnswer,
                'answered_at' => now(),
            ]
        );

        $attempt->update(['last_activity_at' => now()]);

        return response()->json(['status' => 'saved']);
    }

    public function submit(RetakeExam $retakeExam, RetakeExamAttempt $attempt, Request $request): RedirectResponse
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id || $attempt->status !== 'in_progress') {
            return back()->with('error', 'Хатогӣ.');
        }

        $this->processSubmission($attempt, $retakeExam, 'submitted');

        return redirect()->route('student.retake-exams.result', [$retakeExam, $attempt])
            ->with('success', 'Имтиҳон такрорӣ бо муваффақият супорида шуд!');
    }

    public function result(RetakeExam $retakeExam, RetakeExamAttempt $attempt, Request $request): View
    {
        $student = $this->getStudent($request);

        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        $attempt->load(['answers.examQuestion.question.answerOptions', 'answers.question']);

        $showDetails = true;

        return view('student.retake-exams.result', compact('retakeExam', 'attempt', 'showDetails'));
    }

    private function processSubmission(RetakeExamAttempt $attempt, RetakeExam $retakeExam, string $status): void
    {
        DB::transaction(function () use ($attempt, $retakeExam, $status) {
            $answers = RetakeExamAnswer::where('retake_exam_attempt_id', $attempt->id)->get();
            $examQuestions = $retakeExam->mainExam->examQuestions()
                ->with('question.answerOptions')
                ->get()
                ->keyBy('id');

            \Log::info('RetakeExam processSubmission debug', [
                'retake_exam_id' => $retakeExam->id,
                'main_exam_id' => $retakeExam->main_exam_id,
                'student_id' => $attempt->student_id,
                'attempt_id' => $attempt->id,
                'answers_count' => $answers->count(),
                'exam_questions_count' => $examQuestions->count(),
                'answers' => $answers->map(fn($a) => [
                    'exam_question_id' => $a->exam_question_id,
                    'selected_options' => $a->selected_options,
                    'text_answer' => $a->text_answer,
                ])->toArray(),
            ]);

            $totalScore = 0;
            $maxPossible = 0;

            foreach ($examQuestions as $eq) {
                $question = $eq->question;
                $questionWeight = $this->questionWeight($question);
                $maxPossible += $questionWeight;

                $answer = $answers->where('exam_question_id', $eq->id)->first();
                if (!$answer) continue;

                $result = $this->gradingService->gradeRetakeExamAttempt($attempt, $eq, $answer, $questionWeight);

                $answer->update([
                    'is_correct' => $result['is_correct'],
                    'points_earned' => $result['points_earned'],
                    'is_graded' => $result['is_graded'],
                ]);

                $totalScore += $result['points_earned'];
            }

            $percentage = $this->gradingService->calculatePercentage($totalScore, $maxPossible);
            $gradeInfo = $this->gradingService->determineGrade($percentage);

            \Log::info('RetakeExam processSubmission', [
                'student_id' => $attempt->student_id,
                'retake_exam_id' => $retakeExam->id,
                'exam_attempt_id' => $attempt->id,
                'total_score' => $totalScore,
                'max_possible' => $maxPossible,
                'percentage' => $percentage,
                'letter_grade' => $gradeInfo['letter_grade'],
                'is_passing' => $gradeInfo['is_passing'],
            ]);

            $attempt->update([
                'status' => $status,
                'submitted_at' => $status === 'submitted' ? now() : null,
                'auto_submitted_at' => $status === 'auto_submitted' ? now() : null,
                'total_score' => $totalScore,
                'max_possible_score' => $maxPossible,
                'percentage' => $percentage,
                'letter_grade' => $gradeInfo['letter_grade'],
                'grade_point' => $gradeInfo['grade_point'],
            ]);

            $retakeExamStudent = $attempt->retakeExamStudent;
            if (!$retakeExamStudent) {
                $retakeExamStudent = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                    ->where('student_id', $attempt->student_id)
                    ->first();
            }

            if ($retakeExamStudent) {
                $retakeExamStudent->update([
                    'score' => $totalScore,
                    'letter_grade' => $gradeInfo['letter_grade'],
                    'examined_at' => $attempt->submitted_at ?? now(),
                ]);

                \Log::info('RetakeExamStudent updated', [
                    'retake_exam_student_id' => $retakeExamStudent->id,
                    'saved_score' => $totalScore,
                    'saved_letter_grade' => $gradeInfo['letter_grade'],
                ]);

                $debt = $retakeExamStudent->academicDebt;
                if ($debt) {
                    if ($gradeInfo['is_passing']) {
                        $retakeExamStudent->update(['status' => 'passed']);
                        $debt->resolve($percentage, $gradeInfo['letter_grade'], auth()->id());
                    } else {
                        $debt->update([
                            'retake_attempts_used' => DB::raw('retake_attempts_used + 1'),
                        ]);
                        $debt->refresh();

                        $remainingAttempts = ($debt->retake_attempts_used ?? 0);

                        if ($remainingAttempts >= $debt->max_retake_attempts) {
                            $retakeExamStudent->update(['status' => 'failed']);
                            $debt->update([
                                'status' => 'escalated',
                            ]);
                        } else {
                            $retakeExamStudent->update(['status' => 'pending']);
                        }
                    }
                } else {
                    $retakeExamStudent->update([
                        'status' => $gradeInfo['is_passing'] ? 'passed' : 'failed',
                    ]);
                }

                $semesterGrade = SemesterGrade::where('student_id', $attempt->student_id)
                    ->where('subject_assignment_id', $retakeExam->mainExam->subject_assignment_id)
                    ->where('semester_id', $retakeExam->semester_id)
                    ->first();

                if ($semesterGrade) {
                    $semesterGrade->update([
                        'retake_score' => $totalScore,
                        'retake_date' => now(),
                    ]);

                    $gradeCalc = app(\App\Services\GradeCalculator::class);
                    $gradeCalc->recalculateAndPersist(
                        $attempt->student_id,
                        $retakeExam->mainExam->subject_assignment_id,
                        $retakeExam->semester_id
                    );
                }

                if ($gradeInfo['is_passing']) {
                    $this->debtDetector->resolveDebtAfterRetake(
                        $attempt->student_id,
                        $retakeExam->subject_id,
                        $retakeExam->semester_id,
                        $totalScore,
                        $gradeInfo['letter_grade']
                    );
                }

                $this->debtDetector->syncDebtsForSubject(
                    $retakeExam->subject_id,
                    $retakeExam->semester_id,
                    $attempt->student_id
                );
            } else {
                \Log::warning('RetakeExamStudent not found', [
                    'retake_exam_id' => $retakeExam->id,
                    'student_id' => $attempt->student_id,
                    'exam_attempt_id' => $attempt->id,
                ]);
            }
        });
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

    private function getStudent(Request $request): Student
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        return $student;
    }

    private function authorizeStudentRetakeExam(RetakeExam $retakeExam, Student $student): void
    {
        $retakeExamStudent = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$retakeExamStudent) {
            abort(403, 'Шумо ба ин имтиҳон такрорӣ даъват нашудаед.');
        }

        $attemptCount = RetakeExamAttempt::where('retake_exam_id', $retakeExam->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptCount >= $retakeExam->max_attempts) {
            abort(403, 'Шумо ба ҳадди аксари кӯшишҳо расидаед.');
        }
    }
}
