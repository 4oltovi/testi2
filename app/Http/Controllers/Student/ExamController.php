<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Enums\GradeScale;
use App\Models\AnswerOption;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
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
            ->with(['subjectAssignment.curriculum.subject'])
            ->orderBy('starts_at')
            ->get();

        // Кӯшишҳои мавҷудаи донишҷӯ
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
            ->pluck('selected_options', 'exam_question_id')
            ->map(fn($v) => json_decode($v, true));

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

                    $isCorrect = false;
                    $pointsEarned = 0;

                    if (in_array($question->type, ['single_choice', 'true_false'])) {
                        $correctOptions = $question->answerOptions->where('is_correct', true)->pluck('id')->toArray();
                        $selected = json_decode($answer->selected_options ?? '[]', true) ?: [];
                        $isCorrect = !empty($selected) && $selected == $correctOptions;
                        $pointsEarned = $isCorrect ? $questionWeight : 0;
                    } elseif ($question->type === 'multiple_choice') {
                        $correctOptions = $question->answerOptions->where('is_correct', true)->pluck('id')->sort()->values()->toArray();
                        $selected = collect(json_decode($answer->selected_options ?? '[]', true) ?: [])->sort()->values()->toArray();
                        $isCorrect = $selected === $correctOptions;
                        $pointsEarned = $isCorrect ? $questionWeight : 0;
                    } elseif ($question->type === 'matching') {
                        $selectedPairs = [];
                        foreach (explode('||', (string) ($answer->text_answer ?? '')) as $pair) {
                            if (trim($pair) === '') continue;
                            [$qId, $choice] = array_pad(explode(':', $pair, 2), 2, '');
                            $selectedPairs[(string) $qId] = trim((string) $choice);
                        }

                        $correctCount = 0;
                        foreach ($question->answerOptions->where('is_correct', true) as $correctOption) {
                            $expected = trim(explode('|||', $correctOption->option_text, 2)[1] ?? '');
                            $selectedValue = $selectedPairs[(string) $correctOption->id] ?? null;
                            if ($selectedValue !== null && trim((string) $selectedValue) === $expected) {
                                $correctCount++;
                            }
                        }

                        $isCorrect = $correctCount > 0 && $correctCount === $question->answerOptions->where('is_correct', true)->count();
                        $pointsEarned = $correctCount * 2.5;
                }

                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'is_graded' => $question->type !== 'open_text',
                ]);

                $totalScore += $pointsEarned;
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
