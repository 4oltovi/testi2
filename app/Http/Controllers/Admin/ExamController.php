<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
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
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        $query = Exam::query()->with(['subjectAssignment.curriculum.subject', 'group']);

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->latest()->paginate(20);

        return view('admin.exams.index', compact('exams', 'semesters'));
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
        $curriculum = Curriculum::where('subject_id', $subjectId)
            ->where('is_active', true)
            ->first();

        if (!$curriculum) {
            return back()->withErrors(['subject_id' => 'Барои ин фан программаи фаъол ёфт нашуд.'])->withInput();
        }

        $created = 0;

        foreach ($request->group_ids as $groupId) {
            $group = Group::findOrFail($groupId);

            $assignment = SubjectAssignment::firstOrCreate(
                [
                    'curriculum_id' => $curriculum->id,
                    'teacher_id' => $request->user()->id,
                    'group_id' => $group->id,
                    'semester_id' => $semester?->id ?? $curriculum->semester_id,
                ],
                [
                    'lesson_type' => 'theory',
                    'hours_per_week' => 2,
                    'is_active' => true,
                ]
            );

            Exam::create([
                'subject_assignment_id' => $assignment->id,
                'semester_id' => $semester?->id ?? $curriculum->semester_id,
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
        $exam->load(['subjectAssignment.curriculum.subject', 'group']);

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
        $exam->load(['subjectAssignment.curriculum.subject', 'group', 'attempts']);

        return view('admin.exams.show', compact('exam'));
    }

    public function questions(Exam $exam): View
    {
        $exam->load(['subjectAssignment.curriculum.subject', 'group']);

        $examQuestions = $exam->examQuestions()->with('question.answerOptions')->orderBy('sort_order')->get();

        $subjectId = $exam->subjectAssignment?->curriculum?->subject_id;

        $availableQuestions = $subjectId
            ? \App\Models\Question::where('subject_id', $subjectId)
                ->where('is_active', true)
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
                    'points' => \App\Models\Question::find($questionId)?->weighted_points ?? 2.5,
                ]);
            }
        });

        return back()->with('success', 'Саволҳо ба имтиҳон илова карда шуданд.');
    }

    public function results(Exam $exam): View
    {
        $exam->load(['group', 'subjectAssignment.curriculum.subject', 'attempts.student.user']);

        $attempts = $exam->attempts()->with(['student.user'])->orderByDesc('total_score')->get();

        return view('admin.exams.results', compact('exam', 'attempts'));
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $existingQuestionIds = $exam->examQuestions()->pluck('question_id')->all();
        $subjectId = $exam->subjectAssignment?->curriculum?->subject_id;

        if ($subjectId && empty($existingQuestionIds)) {
            $availableQuestions = Question::where('subject_id', $subjectId)
                ->where('is_active', true)
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
            return back()->with('error', 'Барои нашр кардани имтиҳон, шумораи саволҳо бояд ба ' . $exam->total_questions_count . ' расад. Ҳозир ' . $questionsCount . ' мавҷуд аст.');
        }

        $exam->update([
            'status' => $exam->starts_at && $exam->starts_at->isFuture() ? 'scheduled' : 'active',
            'is_published' => true,
        ]);

        return back()->with('success', 'Имтиҳон нашр шуд.');
    }
}
