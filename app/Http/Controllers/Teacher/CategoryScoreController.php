<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Enums\GradeCategory;
use App\Models\CategoryScore;
use App\Models\GradeCategorySetting;
use App\Models\Semester;
use App\Models\SubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryScoreController extends Controller
{
    /**
     * Саҳифаи баҳогузорӣ бо категорияҳо
     */
    public function index(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');
        $semester = $subjectAssignment->semester;

        // Танзимоти категорияҳо
        $categorySettings = GradeCategorySetting::getOrCreateDefaults($subjectAssignment->id);

        // Сана ва шумораи дарс
        $date = $request->get('date', now()->format('Y-m-d'));
        $lessonNumber = (int) $request->get('lesson_number', 1);

        // Баҳоҳои мавҷуда барои ин дарс
        $existingScores = CategoryScore::forLesson($subjectAssignment->id, $date, $lessonNumber)
            ->get()
            ->groupBy(fn($s) => $s->student_id . '_' . $s->category->value);

        return view('teacher.journal.category-scores', compact(
            'subjectAssignment', 'students', 'semester',
            'categorySettings', 'date', 'lessonNumber', 'existingScores'
        ));
    }

    /**
     * Сабти баҳоҳои категориявӣ
     */
    public function store(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $request->validate([
            'date' => 'required|date',
            'lesson_number' => 'required|integer|min:1|max:8',
            'scores' => 'required|array',
            'scores.*' => 'array',
            'scores.*.*' => 'nullable|numeric|min:0',
        ]);

        $semester = $subjectAssignment->semester;
        $categorySettings = GradeCategorySetting::getOrCreateDefaults($subjectAssignment->id);
        // Pluck max_score бо category string value (на Enum object)
        $maxScores = [];
        foreach ($categorySettings as $cs) {
            $catValue = $cs->category instanceof GradeCategory ? $cs->category->value : $cs->category;
            $maxScores[$catValue] = $cs->max_score;
        }
        $date = $request->input('date');
        $lessonNumber = $request->input('lesson_number');

        DB::transaction(function () use ($subjectAssignment, $request, $semester, $maxScores, $date, $lessonNumber) {
            foreach ($request->input('scores') as $studentId => $categories) {
                foreach ($categories as $categoryValue => $score) {
                    if ($score === null || $score === '') continue;

                    $category = GradeCategory::tryFrom($categoryValue);
                    if (!$category) continue;

                    $maxScore = $maxScores[$categoryValue] ?? $category->defaultMaxScore();

                    // Ҳадди аксарро санҷед
                    $score = min((float) $score, $maxScore);

                    CategoryScore::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'subject_assignment_id' => $subjectAssignment->id,
                            'lesson_date' => $date,
                            'lesson_number' => $lessonNumber,
                            'category' => $categoryValue,
                        ],
                        [
                            'semester_id' => $semester->id,
                            'score' => $score,
                            'max_score' => $maxScore,
                            'graded_by' => auth()->id(),
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Баҳоҳо дар 5 категория сабт шуданд.');
    }

    /**
     * Танзимоти категорияҳо (max_score)
     */
    public function settings(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['subject', 'group']);
        $categorySettings = GradeCategorySetting::getOrCreateDefaults($subjectAssignment->id);

        return view('teacher.journal.category-settings', compact('subjectAssignment', 'categorySettings'));
    }

    /**
     * Сабти танзимоти категорияҳо
     */
    public function updateSettings(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $request->validate([
            'settings' => 'required|array',
            'settings.*.category' => 'required|string',
            'settings.*.max_score' => 'required|numeric|min:0.5|max:100',
        ]);

        foreach ($request->input('settings') as $data) {
            GradeCategorySetting::updateOrCreate(
                [
                    'subject_assignment_id' => $subjectAssignment->id,
                    'category' => $data['category'],
                ],
                [
                    'max_score' => $data['max_score'],
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Танзимоти категорияҳо сабт шуд.');
    }

    /**
     * Гузориш — ҷадвали пурра бо ҳамаи дарсҳо
     */
    public function report(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');
        $semester = $subjectAssignment->semester;

        $categorySettings = GradeCategorySetting::getOrCreateDefaults($subjectAssignment->id);

        // Ҳамаи баҳоҳо дар ин семестр
        $allScores = CategoryScore::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->orderBy('lesson_date')
            ->orderBy('lesson_number')
            ->get();

        // Рӯйхати дарсҳои ягона
        $lessons = $allScores->unique(fn($s) => $s->lesson_date->format('Y-m-d') . '_' . $s->lesson_number)
            ->map(fn($s) => ['date' => $s->lesson_date->format('Y-m-d'), 'lesson_number' => $s->lesson_number])
            ->values();

        // Гурӯҳбандӣ: student_id => date_lesson => category => score
        $scoreMatrix = [];
        foreach ($allScores as $score) {
            $key = $score->lesson_date->format('Y-m-d') . '_' . $score->lesson_number;
            $scoreMatrix[$score->student_id][$key][$score->category->value] = $score->score;
        }

        // Маҷмӯъ ва миёна барои ҳар донишҷӯ
        $studentTotals = [];
        foreach ($students as $student) {
            $studentScores = $allScores->where('student_id', $student->id);
            $lessonCount = $studentScores->unique(fn($s) => $s->lesson_date->format('Y-m-d') . '_' . $s->lesson_number)->count();

            $totalScore = $studentScores->sum('score');
            $totalMax = $studentScores->sum('max_score');

            $studentTotals[$student->id] = [
                'total_score' => $totalScore,
                'total_max' => $totalMax,
                'lesson_count' => $lessonCount,
                'percentage' => $totalMax > 0 ? round(($totalScore / $totalMax) * 100, 1) : 0,
            ];
        }

        return view('teacher.journal.category-report', compact(
            'subjectAssignment', 'students', 'semester',
            'categorySettings', 'lessons', 'scoreMatrix', 'studentTotals'
        ));
    }

    /**
     * Санҷиши ки ин таъинот аз они ин омӯзгор аст (admin bypass)
     */
    private function authorizeTeacher(SubjectAssignment $assignment, Request $request): void
    {
        $user = $request->user();

        // Админ ба ҳамаи фанҳо дастрасӣ дорад
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        if ($assignment->teacher_id !== $user->id) {
            abort(403, 'Шумо ба ин фан/гурӯҳ дастрасӣ надоред.');
        }
    }
}
