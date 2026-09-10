<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RatingQuestionsExport;

class RatingQuestionController extends Controller
{
    /**
     * Рӯйхати фанҳо + саволҳои рейтинг
     */
    public function index(Request $request): View
    {
        $subjects = Subject::active()->orderBy('name')->get();
        $subjectId = $request->integer('subject_id') ?: null;

        $counts = Question::join('question_banks as qb', 'qb.id', '=', 'questions.question_bank_id')
            ->where('qb.bank_type', 'rating')
            ->groupBy('qb.subject_id')
            ->selectRaw('qb.subject_id AS sid, COUNT(*) AS cnt')
            ->pluck('cnt', 'sid');

        $questions = collect();
        if ($subjectId) {
            $questions = Question::where('subject_id', $subjectId)
                ->whereHas('questionBank', fn ($q) => $q->where('bank_type', 'rating'))
                ->with('answerOptions')
                ->latest('id')
                ->limit(200)
                ->get();
        }

        return view('admin.rating-questions.index', compact('subjects', 'subjectId', 'counts', 'questions'));
    }

    /**
     * Илова кардани савол (банк автоматӣ сохта мешавад)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'question_text' => 'required|string|max:1000',
            'options' => 'required|array|size:4',
            'options.*' => 'required|string|max:255',
            'correct' => 'required|integer|between:0,3',
            'difficulty_level' => 'nullable|integer|between:1,3',
        ], [
            'options.required' => 'Ҳатман 4 вариант лозим аст.',
            'options.*.required' => 'Ҳамаи вариантҳо бояд пур карда шаванд.',
        ]);

        DB::transaction(function () use ($validated) {
            $bank = QuestionBank::firstOrCreate(
                ['subject_id' => $validated['subject_id'], 'bank_type' => 'rating'],
                [
                    'name' => 'Рейтинг: ' . Subject::find($validated['subject_id'])->name,
                    'teacher_id' => auth()->id(),
                    'is_active' => true,
                ]
            );

            $question = Question::create([
                'question_bank_id' => $bank->id,
                'subject_id' => $validated['subject_id'],
                'type' => 'single_choice',
                'question_text' => $validated['question_text'],
                'difficulty_level' => $validated['difficulty_level'] ?? 1,
                'points' => 2.5,
                'is_active' => true,
            ]);

            foreach (array_values($validated['options']) as $i => $text) {
                AnswerOption::create([
                    'question_id' => $question->id,
                    'option_text' => $text,
                    'is_correct' => $i === (int) $validated['correct'],
                    'sort_order' => $i + 1,
                ]);
            }
        });

        return back()->with('success', '✅ Саволи рейтинг илова шуд.');
    }

    /**
     * Саҳифаи импорт барои саволномаи рейтинг
     */
    public function importForm(): RedirectResponse
    {
        return redirect()->route('admin.rating-questions.import-form');
    }

    /**
     * Импорти Excel барои саволномаи рейтинг
     */
    public function import(Request $request): RedirectResponse
    {
        return redirect()->route('admin.rating-questions.import');
    }

    /**
     * Зерфармоии саволҳои рейтинг барои фан
     */
    public function export(Request $request)
    {
        $subjectId = (int) $request->integer('subject_id');

        if (!$subjectId) {
            return back()->with('error', 'Фанро интихоб кунед.');
        }

        $subject = Subject::findOrFail($subjectId);

        return Excel::download(new RatingQuestionsExport($subjectId), 'rating_questions_' . $subject->name . '.xlsx');
    }

    /**
     * Нест кардани савол
     */
    public function destroy(Question $question): RedirectResponse
    {
        $subjectId = $question->subject_id;

        DB::transaction(function () use ($question) {
            $question->answerOptions()->delete();
            $question->delete();
        });

        return back()->with('success', 'Савол нест шуд.');
    }

    /**
     * Зерфармоии шаблони Excel
     */
    public function downloadTemplate()
    {
        return redirect()->route('admin.rating-questions.template');
    }
}