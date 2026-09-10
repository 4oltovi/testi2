<?php

namespace App\Http\Controllers\Admin;

use App\Exports\QuestionsExport;
use App\Http\Controllers\Controller;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    /**
     * Рӯйхати саволҳо
     */
    public function index(Request $request): View
    {
        $questions = Question::with(['answerOptions'])
            ->whereHas('questionBank', fn ($q) => $q->where('bank_type', 'exam'))
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(30);

        $subjects = Subject::orderBy('name')->get();
        $questionCounts = Question::whereHas('questionBank', fn ($q) => $q->where('bank_type', 'exam'))
            ->selectRaw('subject_id, COUNT(*) as aggregate')
            ->groupBy('subject_id')
            ->pluck('aggregate', 'subject_id');

        return view('admin.questions.index', compact('questions', 'subjects', 'questionCounts'));
    }

    /**
     * Формаи эҷоди савол
     */
    public function create(Request $request): View
    {
        $subjects = Subject::orderBy('name')->get();
        $selectedSubject = $request->subject_id ? Subject::find($request->subject_id) : null;
        return view('admin.questions.create', compact('subjects', 'selectedSubject'));
    }

    /**
     * Сабти савол
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required|in:single_choice,multiple_choice,true_false,matching',
            'question_text' => 'required|string|max:5000',
            'difficulty_level' => 'required|integer|min:1|max:5',
            'explanation' => 'nullable|string|max:2000',
            'question_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'options' => 'nullable|array',
            'options.*.text' => 'nullable|string|max:255',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        $this->validateQuestionOptions($request);

        $defaultPoints = $this->defaultQuestionPoints($request->type);
        $questionImagePath = $this->uploadQuestionImage($request->file('question_image'));

        DB::transaction(function () use ($request, $defaultPoints, $questionImagePath) {
            $question = Question::create([
                'question_bank_id' => $this->getOrCreateDefaultBank($request->subject_id),
                'subject_id' => $request->subject_id,
                'type' => $request->type,
                'question_text' => $request->question_text,
                'difficulty_level' => $request->difficulty_level,
                'points' => $defaultPoints,
                'explanation' => $request->explanation,
                'question_image' => $questionImagePath,
                'is_active' => true,
            ]);

            if ($request->type === 'matching') {
                // Мувофиқоварӣ: sub_questions = зерсаволҳо, matching_extra = ҷавобҳои иловагӣ
                if ($request->has('sub_questions')) {
                    foreach ($request->sub_questions as $index => $sq) {
                        if (empty($sq['text'])) continue;
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $sq['text'] . '|||' . ($sq['match'] ?? ''),
                            'is_correct' => true,
                            'sort_order' => $index,
                        ]);
                    }
                }
                // Ҷавобҳои иловагӣ (нодуруст)
                if ($request->has('matching_extra')) {
                    foreach ($request->matching_extra as $index => $opt) {
                        if (empty($opt['text'])) continue;
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $opt['text'],
                            'is_correct' => false,
                            'sort_order' => 100 + $index,
                        ]);
                    }
                }
            } else {
                // Якҷавобӣ, чандҷавобӣ, дуруст/нодуруст
                foreach ($request->options as $index => $option) {
                    if (empty($option['text'])) continue;
                    AnswerOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option['text'],
                        'is_correct' => !empty($option['is_correct']),
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        return redirect()->route('admin.exams.questions.create', ['subject_id' => $request->subject_id])
            ->with('success', 'Савол илова шуд. Саволи навбатиро ворид кунед.');
    }

    /**
     * Таҳрири савол
     */
    public function edit(Question $question): View
    {
        $question->load('answerOptions');
        $subjects = Subject::orderBy('name')->get();
        return view('admin.questions.edit', compact('question', 'subjects'));
    }

    /**
     * Навсозии савол
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required|in:single_choice,multiple_choice,true_false,matching',
            'question_text' => 'required|string|max:5000',
            'difficulty_level' => 'required|integer|min:1|max:5',
            'explanation' => 'nullable|string|max:2000',
            'question_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'options' => 'nullable|array',
            'options.*.text' => 'nullable|string|max:255',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        $this->validateQuestionOptions($request);

        $questionImagePath = $this->uploadQuestionImage($request->file('question_image'), $question->question_image);

        DB::transaction(function () use ($request, $question, $questionImagePath) {
            $question->update([
                'subject_id' => $request->subject_id,
                'type' => $request->type,
                'question_text' => $request->question_text,
                'difficulty_level' => $request->difficulty_level,
                'explanation' => $request->explanation,
                'question_image' => $questionImagePath,
            ]);

            $question->answerOptions()->delete();

            if ($request->type === 'matching') {
                if ($request->has('sub_questions')) {
                    foreach ($request->sub_questions as $index => $sq) {
                        if (empty($sq['text'])) continue;
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $sq['text'] . '|||' . ($sq['match'] ?? ''),
                            'is_correct' => true,
                            'sort_order' => $index,
                        ]);
                    }
                }
                if ($request->has('matching_extra')) {
                    foreach ($request->matching_extra as $index => $opt) {
                        if (empty($opt['text'])) continue;
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $opt['text'],
                            'is_correct' => false,
                            'sort_order' => 100 + $index,
                        ]);
                    }
                }
            } else {
                foreach ($request->options as $index => $option) {
                    if (empty($option['text'])) continue;
                    AnswerOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option['text'],
                        'is_correct' => !empty($option['is_correct']),
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        return redirect()->route('admin.exams.questions.index')
            ->with('success', 'Савол навсозӣ шуд.');
    }

    /**
     * Зерфармоии саволҳо
     */
    public function export(Request $request)
    {
        $subjectId = (int) $request->integer('subject_id');

        if (!$subjectId) {
            return back()->with('error', 'Фанро интихоб кунед.');
        }

        $subject = Subject::findOrFail($subjectId);

        return Excel::download(new QuestionsExport($subjectId), 'questions_' . $subject->name . '.xlsx');
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
     * Санҷиши дурусти варианти ҷавобҳо
     */
    private function validateQuestionOptions(Request $request): void
    {
        if ($request->type === 'matching') {
            return;
        }

        $options = $request->input('options', []);

        // Филтр кардани вариантҳои холӣ (текст надоранд)
        $validOptions = collect($options)->filter(function ($option) {
            return isset($option['text']) && trim((string)$option['text']) !== '';
        });

        $textsCount = $validOptions->count();

        // Ҳисоб кардани шумораи ҷавобҳои дуруст танҳо аз вариантҳои муътабар
        $correctCount = $validOptions->filter(function ($option) {
            $isCorrect = $option['is_correct'] ?? false;
            return filter_var($isCorrect, FILTER_VALIDATE_BOOLEAN) || $isCorrect === '1' || $isCorrect === 1 || $isCorrect === true;
        })->count();

        // Санҷиши шумораи вариантҳо вобаста ба намуди савол
        $type = $request->type;

        if ($type === 'true_false') {
            // Барои саволи дуруст/нодуруст ҳадди ақал 2 вариант лозим аст
            if ($textsCount < 2) {
                abort(422, "Саволи дуруст/нодуруст бояд ҳадди аққал 2 варианти ҷавоб дошта бошад.");
            }
        } elseif (in_array($type, ['single_choice', 'multiple_choice'])) {
            // Барои якҷавобӣ ва чандҷавобӣ ҳадди ақал 4 вариант лозим аст
            if ($textsCount < 4) {
                abort(422, "Саволи якҷавобӣ/чандҷавобӣ бояд ҳадди аққал 4 варианти ҷавоб дошта бошад. (Ҳозира: {$textsCount})");
            }
        }

        // Санҷиши шумораи ҷавобҳои дуруст
        if (in_array($type, ['single_choice', 'true_false'], true) && $correctCount !== 1) {
            abort(422, 'Саволи якҷавобӣ/дуруст-нуҳуфт бояд танҳо 1 ҷавоби дуруст дошта бошад. (Ҳозира: ' . $correctCount . ')');
        }

        // Санҷиш барои чандҷавобӣ
        if ($type === 'multiple_choice' && $correctCount < 1) {
            abort(422, 'Саволи чандҷавобӣ бояд ҳадди аққал 1 ҷавоби дуруст дошта бошад.');
        }
    }

    /**
     * Балли пешфарзи савол
     */
    private function defaultQuestionPoints(string $type): float
    {
        return match ($type) {
            'matching' => 10.0,
            'single_choice', 'multiple_choice', 'true_false' => 2.5,
            default => 2.5,
        };
    }

    /**
     * Саҳифаи импорт барои саволҳо
     */
    public function importForm(): RedirectResponse
    {
        return redirect()->route('admin.questions.excel-import-form');
    }

    /**
     * Импорти Excel барои саволҳо
     */
    public function import(Request $request): RedirectResponse
    {
        return redirect()->route('admin.questions.excel-import-upload');
    }

    /**
     * Зерфармоии шаблони Excel
     */
    public function downloadTemplate()
    {
        return redirect()->route('admin.questions.excel-import-template');
    }

    /**
     * Банки default барои фан (автоматикӣ месозад)
     */
    private function getOrCreateDefaultBank(int $subjectId): int
    {
        $bank = \App\Models\QuestionBank::where('subject_id', $subjectId)
            ->where('bank_type', 'exam')
            ->first();
        if ($bank) return $bank->id;

        $subject = Subject::find($subjectId);
        $bank = \App\Models\QuestionBank::create([
            'subject_id' => $subjectId,
            'teacher_id' => auth()->id(),
            'name' => 'Саволҳои ' . ($subject->name ?? 'Фан'),
            'bank_type' => 'exam',
            'is_active' => true,
        ]);
        return $bank->id;
    }

    /**
     * Бор кардани акси савол
     */
    private function uploadQuestionImage(?object $file, ?string $existingPath = null): ?string
    {
        if (!$file) {
            return $existingPath;
        }

        if (!is_dir(public_path('images/questions'))) {
            mkdir(public_path('images/questions'), 0755, true);
        }

        if ($existingPath && file_exists(public_path($existingPath))) {
            unlink(public_path($existingPath));
        }

        $filename = 'question_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/questions'), $filename);

        return 'images/questions/' . $filename;
    }
}
