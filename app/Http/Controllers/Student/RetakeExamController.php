<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\RetakeExam;
use App\Models\RetakeExamAnswer;
use App\Models\RetakeExamAttempt;
use App\Models\RetakeExamQuestion;
use App\Models\RetakeExamStudent;
use App\Models\Student;
use App\Services\ExamGradingService;
use App\Services\GradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RetakeExamController extends Controller
{
    private ExamGradingService $gradingService;

    public function __construct(ExamGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
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

        $examQuestions = RetakeExamQuestion::where('retake_exam_id', $retakeExam->id)
            ->with('question.answerOptions')
            ->orderBy('sort_order')
            ->get();

        if ($examQuestions->isEmpty()) {
            return back()->with('error', 'Ин имтиҳон ҳанӯз савол надорад.');
        }

        $existingAnswers = RetakeExamAnswer::where('retake_exam_attempt_id', $attempt->id)
            ->pluck('selected_options', 'retake_exam_question_id');

        $remainingSeconds = $retakeExam->duration_minutes * 60;

        return view('student.retake-exams.take', compact('retakeExam', 'attempt', 'examQuestions', 'existingAnswers', 'remainingSeconds'));
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
            'retake_exam_question_id' => 'required|exists:retake_exam_questions,id',
            'selected_options' => 'nullable|array',
            'text_answer' => 'nullable|string',
        ]);

        $examQuestion = RetakeExamQuestion::findOrFail($request->retake_exam_question_id);

        RetakeExamAnswer::updateOrCreate(
            [
                'retake_exam_attempt_id' => $attempt->id,
                'retake_exam_question_id' => $examQuestion->id,
            ],
            [
                'question_id' => $examQuestion->question_id,
                'selected_options' => $request->selected_options ? json_encode($request->selected_options) : null,
                'text_answer' => $request->text_answer,
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

        $attempt->load(['answers.question.answerOptions', 'answers.retakeExamQuestion']);

        $showDetails = true;

        return view('student.retake-exams.result', compact('retakeExam', 'attempt', 'showDetails'));
    }

    private function processSubmission(RetakeExamAttempt $attempt, RetakeExam $retakeExam, string $status): void
    {
        DB::transaction(function () use ($attempt, $retakeExam, $status) {
            $answers = RetakeExamAnswer::where('retake_exam_attempt_id', $attempt->id)->get();
            $examQuestions = RetakeExamQuestion::where('retake_exam_id', $retakeExam->id)
                ->with('question.answerOptions')
                ->get()
                ->keyBy('id');

            $totalScore = 0;
            $maxPossible = 0;

            foreach ($examQuestions as $eq) {
                $question = $eq->question;
                $questionWeight = $this->questionWeight($question);
                $maxPossible += $questionWeight;

                $answer = $answers->where('retake_exam_question_id', $eq->id)->first();
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
                if ($retakeExamStudent) {
                    $retakeExamStudent->update([
                        'score' => $percentage,
                        'letter_grade' => $gradeInfo['letter_grade'],
                        'examined_at' => now(),
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

                            $remainingAttempts = ($debt->retake_attempts_used ?? 0) + 1;

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
