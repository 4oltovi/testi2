<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $questions = Question::whereHas('questionBank', fn($q) => $q->where('teacher_id', $request->user()->id)->where('bank_type', 'exam'))
            ->with('questionBank')
            ->latest()
            ->paginate(30);
        return view('teacher.questions.index', compact('questions'));
    }

    public function create() { return view('teacher.questions.create'); }
    public function store(Request $request) { return back()->with('info', 'Дар коркард...'); }
    public function edit(Question $question) { return view('teacher.questions.edit', compact('question')); }
    public function update(Request $request, Question $question) { return back(); }

    /**
     * Нест кардани савол (танҳо аз тарафи омўзгори соқиб)
     */
    public function destroy(Question $question, Request $request): RedirectResponse
    {
        if ($question->questionBank?->teacher_id !== $request->user()->id) {
            abort(403, 'Шумо ҳуқуқи нест кардани ин саволро надоред.');
        }

        $question->answerOptions()->delete();
        $question->delete();

        return back()->with('success', 'Савол нест шуд.');
    }
}
