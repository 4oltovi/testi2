<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Group;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $academicYearId = $request->get('academic_year_id');
        $semesterId = $request->get('semester_id');

        $query = Semester::with('academicYear')->orderByDesc('start_date');
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        $semesters = $query->get();
        $academicYears = \App\Models\AcademicYear::orderByDesc('start_date')->get();

        $query = Exam::query()->with(['subjectAssignment.subject', 'group']);

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->latest()->paginate(20);

        return view('admin.exams.index', compact('exams', 'semesters', 'academicYears', 'academicYearId'));
    }

    public function create(): View
    {
        $subjects = Subject::orderBy('name')->get();
        $groups = Group::where('is_active', true)->orderBy('name')->get();

        return view('admin.exams.create', compact('subjects', 'groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'group_ids' => 'required|array|min:1',
            'group_ids.*' => 'integer|exists:groups,id',
            'title' => 'nullable|string|max:255',
            'exam_type' => 'required|in:main,retake,retake_commission,rating1,rating2,midterm,quiz',
            'format' => 'required|in:online_test,written,oral,mixed',
            'duration_minutes' => 'required|integer|min:5|max:180',
            'passing_score' => 'required|numeric|min:0|max:100',
            'max_attempts' => 'required|integer|min:1|max:5',
            'total_questions_count' => 'required|integer|min:1|max:100',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $semester = Semester::current();
        $subjectId = $request->subject_id;

        // Санҷиш: оё фан мавҷуд аст?
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return back()->withErrors(['subject_id' => 'Фан ёфт нашуд.'])->withInput();
        }

        $created = 0;

        foreach ($request->group_ids as $groupId) {
            $group = Group::findOrFail($groupId);

            $assignment = SubjectAssignment::firstOrCreate(
                [
                    'subject_id' => $subjectId,
                    'teacher_id' => $request->user()->id,
                    'group_id' => $group->id,
                    'semester_id' => $semester?->id,
                ],
                [
                    'lesson_type' => 'theory',
                    'hours_per_week' => 2,
                    'is_active' => true,
                ]
            );

            Exam::create([
                'subject_assignment_id' => $assignment->id,
                'semester_id' => $semester?->id,
                'teacher_id' => $request->user()->id,
                'group_id' => $group->id,
                'title' => $request->title ?: 'Имтиҳони ' . $group->name,
                'description' => $request->description ?? null,
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

            $created++;
        }

        return redirect()->route('admin.exams.index')->with('success', $created . ' имтиҳон сохта шуд.');
    }

    public function edit(Exam $exam): View
    {
        $exam->load(['subjectAssignment.subject', 'group']);

        return view('admin.exams.edit', compact('exam'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_type' => 'required|in:main,retake,retake_commission,rating1,rating2,midterm,quiz',
            'format' => 'required|in:online_test,written,oral,mixed',
            'duration_minutes' => 'required|integer|min:5|max:180',
            'total_questions_count' => 'required|integer|min:1|max:100',
            'passing_score' => 'required|numeric|min:0|max:100',
            'max_attempts' => 'required|integer|min:1|max:5',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_answers' => 'nullable|boolean',
            'show_results_immediately' => 'nullable|boolean',
            'allow_back_navigation' => 'nullable|boolean',
        ]);

        $exam->update([
            'title' => $request->title,
            'description' => $request->description,
            'exam_type' => $request->exam_type,
            'format' => $request->format,
            'duration_minutes' => $request->duration_minutes,
            'total_questions_count' => $request->total_questions_count,
            'passing_score' => $request->passing_score,
            'max_attempts' => $request->max_attempts,
            'shuffle_questions' => $request->boolean('shuffle_questions', false),
            'shuffle_answers' => $request->boolean('shuffle_answers', false),
            'show_results_immediately' => $request->boolean('show_results_immediately', false),
            'allow_back_navigation' => $request->boolean('allow_back_navigation', true),
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', 'Маълумоти имтиҳон навсозӣ шуд.');
    }

    public function show(Exam $exam): View
    {
        $exam->load(['subjectAssignment.subject', 'group', 'attempts']);

        return view('admin.exams.show', compact('exam'));
    }

    public function questions(Exam $exam): View
    {
        $exam->load(['subjectAssignment.subject', 'group']);

        $examQuestions = $exam->examQuestions()->with('question.answerOptions')->orderBy('sort_order')->get();

        $subjectId = $exam->subjectAssignment?->subject_id;

        $availableQuestions = $subjectId
            ? Question::where('subject_id', $subjectId)
            ->where('is_active', true)
            ->whereHas('questionBank', fn($q) => $q->where('bank_type', 'exam'))
            ->whereNotIn('id', $examQuestions->pluck('question_id'))
            ->with('answerOptions')
            ->orderBy('difficulty_level')
            ->get()
            : collect();

        return view('admin.exams.questions', compact('exam', 'examQuestions', 'availableQuestions'));
    }

    public function addQuestions(Exam $exam, Request $request): RedirectResponse
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $invalidQuestions = Question::whereIn('id', $request->question_ids)
            ->whereHas('questionBank', fn($q) => $q->where('bank_type', '!=', 'exam'))
            ->count();

        if ($invalidQuestions > 0) {
            return back()->with('error', 'Баъзе саволҳо ба банки имтиҳон тааллуқ надоранд. Танҳо саволҳои имтиҳонро илова кардан мумкин аст.');
        }

        DB::transaction(function () use ($exam, $request) {
            $nextSort = $exam->examQuestions()->max('sort_order') ?? 0;

            foreach ($request->question_ids as $questionId) {
                if ($exam->examQuestions()->where('question_id', $questionId)->exists()) {
                    continue;
                }

                $nextSort++;
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_id' => $questionId,
                    'sort_order' => $nextSort,
                    'points' => Question::find($questionId)?->weighted_points ?? 2.5,
                ]);
            }
        });

        return back()->with('success', 'Саволҳо ба имтиҳон илова карда шуданд.');
    }

    public function results(Exam $exam): View
    {
        $exam->load(['group', 'subjectAssignment.subject', 'attempts.student.user']);

        $attempts = $exam->attempts()->with(['student.user'])->orderByDesc('total_score')->get();

        return view('admin.exams.results', compact('exam', 'attempts'));
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $subjectAssignment = $exam->subjectAssignment;
        $subjectId = $subjectAssignment?->subject_id;

        if (!$subjectId) {
            return back()->with('error', 'Имтиҳон бо фан алоқаманд нест. Аввал фанро танзим кунед.');
        }

        $existingQuestionIds = $exam->examQuestions()->pluck('question_id')->all();

        $nonExamQuestions = Question::whereIn('id', $existingQuestionIds)
            ->whereHas('questionBank', fn($q) => $q->where('bank_type', '!=', 'exam'))
            ->count();

        if ($nonExamQuestions > 0) {
            $exam->examQuestions()
                ->whereHas('question.questionBank', fn($q) => $q->where('bank_type', '!=', 'exam'))
                ->delete();
            $existingQuestionIds = $exam->examQuestions()->pluck('question_id')->all();
        }

        $wrongSubjectQuestions = Question::whereIn('id', $existingQuestionIds)
            ->where('subject_id', '!=', $subjectId)
            ->count();

        if ($wrongSubjectQuestions > 0) {
            $exam->examQuestions()
                ->whereHas('question', fn($q) => $q->where('subject_id', '!=', $subjectId))
                ->delete();
            $existingQuestionIds = $exam->examQuestions()->pluck('question_id')->all();
        }

        if (empty($existingQuestionIds)) {
            $availableQuestions = Question::where('subject_id', $subjectId)
                ->where('is_active', true)
                ->whereHas('questionBank', fn($q) => $q->where('bank_type', 'exam'))
                ->whereNotIn('id', $existingQuestionIds)
                ->orderBy('difficulty_level')
                ->limit((int) $exam->total_questions_count)
                ->get();

            if ($availableQuestions->count() >= $exam->total_questions_count) {
                foreach ($availableQuestions as $index => $question) {
                    ExamQuestion::create([
                        'exam_id' => $exam->id,
                        'question_id' => $question->id,
                        'sort_order' => $index + 1,
                        'points' => $question->weighted_points ?? 2.5,
                    ]);
                }
            }
        }

        $questionsCount = $exam->examQuestions()->count();

        if ($questionsCount < $exam->total_questions_count) {
            return back()->with('error', 'Барои нашр кардани имтиҳон, шумораи саволҳо бояд ба ' . $exam->total_questions_count . ' расад. Ҳозир ' . $questionsCount . ' мавҷуд аст. Банки саволҳои имтиҳонро санҷед.');
        }

        $exam->update([
            'status' => $exam->starts_at && $exam->starts_at->isFuture() ? 'scheduled' : 'active',
            'is_published' => true,
        ]);

        return back()->with('success', 'Имтиҳон нашр шуд.');
    }
}
