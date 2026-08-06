<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\SubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Рӯйхати имтиҳонҳо
     */
    public function index(Request $request): View
    {
        $semester = Semester::current();
        $exams = Exam::where('teacher_id', $request->user()->id)
            ->when($semester, fn($q) => $q->where('semester_id', $semester->id))
            ->with(['subjectAssignment.curriculum.subject', 'group'])
            ->latest()
            ->paginate(20);

        return view('teacher.exams.index', compact('exams', 'semester'));
    }

    /**
     * Формаи эҷоди тест
     */
    public function create(Request $request): View
    {
        $teacher = $request->user();
        $semester = Semester::current();

        $assignments = SubjectAssignment::where('teacher_id', $teacher->id)
            ->where('semester_id', $semester?->id)
            ->where('is_active', true)
            ->with(['curriculum.subject', 'group'])
            ->get();

        // Танзимоти тест аз Settings
        $testSettings = [
            'total_questions' => (int) Setting::get('test_total_questions', 25),
            'time_per_question' => (int) Setting::get('test_time_per_question', 1),
            'total_time' => (int) Setting::get('test_total_time', 25),
            'auto_submit' => (bool) Setting::get('test_auto_submit', true),
        ];

        return view('teacher.exams.create', compact('assignments', 'semester', 'testSettings'));
    }

    /**
     * Сабти тести нав
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_type' => 'required|in:main,retake,retake_commission,rating1,rating2,midterm,quiz',
            'format' => 'required|in:online_test,written,oral,mixed',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'total_questions_count' => 'required|integer|min:1|max:100',
            'passing_score' => 'required|numeric|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results_immediately' => 'boolean',
            'allow_back_navigation' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:5',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $assignment = SubjectAssignment::findOrFail($request->subject_assignment_id);

        // Санҷиши ки ин фан аз они устод аст
        if ($assignment->teacher_id !== $request->user()->id) {
            abort(403, 'Шумо ба ин фан дастрасӣ надоред.');
        }

        $semester = Semester::current();

        $exam = Exam::create([
            'subject_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'teacher_id' => $request->user()->id,
            'group_id' => $assignment->group_id,
            'title' => $request->title,
            'description' => $request->description,
            'exam_type' => $request->exam_type,
            'format' => $request->format,
            'duration_minutes' => $request->duration_minutes,
            'total_questions_count' => $request->total_questions_count,
            'passing_score' => $request->passing_score,
            'shuffle_questions' => $request->boolean('shuffle_questions', true),
            'shuffle_answers' => $request->boolean('shuffle_answers', true),
            'show_results_immediately' => $request->boolean('show_results_immediately', false),
            'allow_back_navigation' => $request->boolean('allow_back_navigation', true),
            'max_attempts' => $request->max_attempts,
            'auto_save' => true,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'status' => 'draft',
            'is_published' => false,
        ]);

        return redirect()->route('teacher.exams.show', $exam)
            ->with('success', 'Тест бо муваффақият сохта шуд. Ҳоло саволҳоро илова кунед.');
    }

    /**
     * Намоиш ва идоракунии тест
     */
    public function show(Exam $exam, Request $request): View
    {
        $this->authorizeExam($exam, $request);

        $exam->load([
            'subjectAssignment.curriculum.subject',
            'group',
            'examQuestions.question.answerOptions',
        ]);

        // Саволҳои мавҷуда
        $examQuestions = $exam->examQuestions()->with('question.answerOptions')->orderBy('sort_order')->get();

        // Саволҳои дастрас барои илова (аз банкҳои ин фан)
        $subjectId = $exam->subjectAssignment->curriculum->subject_id;
        $availableQuestions = Question::where('subject_id', $subjectId)
            ->where('is_active', true)
            ->whereNotIn('id', $examQuestions->pluck('question_id'))
            ->with('answerOptions')
            ->orderBy('difficulty_level')
            ->get();

        // Банкҳои саволҳо
        $questionBanks = QuestionBank::where('subject_id', $subjectId)
            ->where('is_active', true)
            ->withCount('questions')
            ->get();

        return view('teacher.exams.show', compact('exam', 'examQuestions', 'availableQuestions', 'questionBanks'));
    }

    /**
     * Таҳрири тест
     */
    public function edit(Exam $exam, Request $request): View
    {
        $this->authorizeExam($exam, $request);

        if ($exam->status !== 'draft') {
            return redirect()->route('teacher.exams.show', $exam)
                ->with('error', 'Танҳо тести лоиҳавиро таҳрир кардан мумкин аст.');
        }

        $teacher = $request->user();
        $semester = Semester::current();

        $assignments = SubjectAssignment::where('teacher_id', $teacher->id)
            ->where('semester_id', $semester?->id)
            ->where('is_active', true)
            ->with(['curriculum.subject', 'group'])
            ->get();

        $testSettings = [
            'total_questions' => (int) Setting::get('test_total_questions', 25),
            'time_per_question' => (int) Setting::get('test_time_per_question', 1),
            'total_time' => (int) Setting::get('test_total_time', 25),
            'auto_submit' => (bool) Setting::get('test_auto_submit', true),
        ];

        return view('teacher.exams.edit', compact('exam', 'assignments', 'semester', 'testSettings'));
    }

    /**
     * Навсозии тест
     */
    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam, $request);

        if ($exam->status !== 'draft') {
            return back()->with('error', 'Танҳо тести лоиҳавиро таҳрир кардан мумкин аст.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_type' => 'required|in:main,retake,retake_commission,rating1,rating2,midterm,quiz',
            'format' => 'required|in:online_test,written,oral,mixed',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'total_questions_count' => 'required|integer|min:1|max:100',
            'passing_score' => 'required|numeric|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results_immediately' => 'boolean',
            'allow_back_navigation' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:5',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $exam->update([
            'title' => $request->title,
            'description' => $request->description,
            'exam_type' => $request->exam_type,
            'format' => $request->format,
            'duration_minutes' => $request->duration_minutes,
            'total_questions_count' => $request->total_questions_count,
            'passing_score' => $request->passing_score,
            'shuffle_questions' => $request->boolean('shuffle_questions', true),
            'shuffle_answers' => $request->boolean('shuffle_answers', true),
            'show_results_immediately' => $request->boolean('show_results_immediately', false),
            'allow_back_navigation' => $request->boolean('allow_back_navigation', true),
            'max_attempts' => $request->max_attempts,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        return redirect()->route('teacher.exams.show', $exam)
            ->with('success', 'Тест навсозӣ шуд.');
    }

    /**
     * Натиҷаҳои имтиҳон
     */
    public function results(Exam $exam, Request $request): View
    {
        $this->authorizeExam($exam, $request);

        $exam->load(['group', 'subjectAssignment.curriculum.subject']);

        $attempts = $exam->attempts()
            ->with(['student.user'])
            ->orderByDesc('total_score')
            ->get();

        $statistics = [
            'total_students' => $attempts->unique('student_id')->count(),
            'completed' => $attempts->whereIn('status', ['submitted', 'auto_submitted', 'graded'])->count(),
            'in_progress' => $attempts->where('status', 'in_progress')->count(),
            'average_score' => $attempts->whereNotNull('percentage')->avg('percentage'),
            'max_score' => $attempts->whereNotNull('percentage')->max('percentage'),
            'min_score' => $attempts->whereNotNull('percentage')->min('percentage'),
            'passed' => $attempts->where('percentage', '>=', $exam->passing_score)->count(),
            'failed' => $attempts->where('percentage', '<', $exam->passing_score)->whereNotNull('percentage')->count(),
        ];

        return view('teacher.exams.results', compact('exam', 'attempts', 'statistics'));
    }

    /**
     * Нашри тест (Draft → Scheduled/Active)
     */
    public function publish(Exam $exam, Request $request): RedirectResponse
    {
        $this->authorizeExam($exam, $request);

        // Санҷиш: шумораи саволҳо кофӣ?
        $questionsCount = $exam->examQuestions()->count();
        if ($questionsCount < $exam->total_questions_count) {
            return back()->with('error',
                "Шумораи саволҳо кофӣ нест. Лозим: {$exam->total_questions_count}, мавҷуд: {$questionsCount}");
        }

        DB::transaction(function () use ($exam) {
            $newStatus = $exam->starts_at && $exam->starts_at->isFuture()
                ? 'scheduled'
                : 'active';

            $exam->update([
                'status' => $newStatus,
                'is_published' => true,
            ]);
        });

        return back()->with('success', 'Тест бо муваффақият нашр шуд!');
    }

    /**
     * Илова кардани саволҳо ба тест
     */
    public function addQuestions(Exam $exam, Request $request): RedirectResponse
    {
        $this->authorizeExam($exam, $request);

        if ($exam->status !== 'draft') {
            return back()->with('error', 'Танҳо ба тести лоиҳавӣ савол илова кардан мумкин аст.');
        }

        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $currentMax = $exam->examQuestions()->max('sort_order') ?? 0;
        $questionsAdded = 0;

        DB::transaction(function () use ($exam, $request, &$currentMax, &$questionsAdded) {
            foreach ($request->question_ids as $questionId) {
                // Нест ки такрор шавад
                $exists = ExamQuestion::where('exam_id', $exam->id)
                    ->where('question_id', $questionId)
                    ->exists();

                if (!$exists) {
                    $question = Question::find($questionId);
                    $currentMax++;

                    ExamQuestion::create([
                        'exam_id' => $exam->id,
                        'question_id' => $questionId,
                        'sort_order' => $currentMax,
                        'points' => $question->points ?? 1.00,
                    ]);

                    $questionsAdded++;
                }
            }
        });

        return back()->with('success', "{$questionsAdded} савол ба тест илова шуд.");
    }

    /**
     * Нест кардани савол аз тест
     */
    public function removeQuestion(Exam $exam, ExamQuestion $examQuestion, Request $request): RedirectResponse
    {
        $this->authorizeExam($exam, $request);

        if ($exam->status !== 'draft') {
            return back()->with('error', 'Танҳо аз тести лоиҳавӣ савол нест кардан мумкин аст.');
        }

        $examQuestion->delete();

        return back()->with('success', 'Савол нест карда шуд.');
    }

    /**
     * Санҷиши ки ин тест аз они ин омӯзгор аст
     */
    private function authorizeExam(Exam $exam, Request $request): void
    {
        if ($exam->teacher_id !== $request->user()->id) {
            abort(403, 'Шумо ба ин тест дастрасӣ надоред.');
        }
    }
}
