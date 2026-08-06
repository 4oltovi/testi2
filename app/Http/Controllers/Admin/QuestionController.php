<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestionController extends Controller
{
    /**
     * Рӯйхати саволҳо
     */
    public function index(Request $request): View
    {
        $questions = Question::with(['answerOptions'])
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(30);

        $subjects = Subject::orderBy('name')->get();

        return view('admin.questions.index', compact('questions', 'subjects'));
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
            'options' => 'nullable|array',
            'options.*.text' => 'nullable|string|max:255',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        $this->validateQuestionOptions($request);

        // Балл аз танзимот
        $defaultPoints = $this->defaultQuestionPoints($request->type);

        DB::transaction(function () use ($request, $defaultPoints) {
            $question = Question::create([
                'question_bank_id' => $this->getOrCreateDefaultBank($request->subject_id),
                'subject_id' => $request->subject_id,
                'type' => $request->type,
                'question_text' => $request->question_text,
                'difficulty_level' => $request->difficulty_level,
                'points' => $defaultPoints,
                'explanation' => $request->explanation,
                'is_active' => true,
            ]);

            if ($request->type === 'matching') {
                // Мувофиқоварӣ: sub_questions = зерсаволҳо, options = ҷавобҳо
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
                if ($request->has('options')) {
                    foreach ($request->options as $index => $opt) {
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
            'options' => 'nullable|array',
            'options.*.text' => 'nullable|string|max:255',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        $this->validateQuestionOptions($request);

        DB::transaction(function () use ($request, $question) {
            $question->update([
                'subject_id' => $request->subject_id,
                'type' => $request->type,
                'question_text' => $request->question_text,
                'difficulty_level' => $request->difficulty_level,
                'explanation' => $request->explanation,
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
                if ($request->has('options')) {
                    foreach ($request->options as $index => $opt) {
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
     * Нест кардан
     */
    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();
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
     * Импорт form
     */
    public function importForm(): View
    {
        $subjects = Subject::orderBy('name')->get();
        return view('admin.questions.import', compact('subjects'));
    }

    /**
     * Шаблони CSV
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $csvPath = storage_path('app/templates/questions_import_template.csv');
        if (!is_dir(dirname($csvPath))) {
            mkdir(dirname($csvPath), 0755, true);
        }

        $headers = ['question_text', 'type', 'difficulty_level', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct_answer', 'explanation'];

        $examples = [
            ['Пойтахти Тоҷикистон кадом шаҳр аст?', 'single_choice', '1', 'Душанбе', 'Хуҷанд', 'Бохтар', 'Кӯлоб', '', 'a', 'Душанбе пойтахт аст'],
            ['PHP забони барномасозӣ аст', 'true_false', '1', 'Дуруст', 'Нодуруст', '', '', '', 'a', ''],
            ['Кадомашон забони барномасозӣ?', 'multiple_choice', '2', 'Python', 'HTML', 'Java', 'CSS', '', 'ac', 'Python ва Java'],
        ];

        $file = fopen($csvPath, 'w');
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, $headers);
        foreach ($examples as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return response()->download($csvPath, 'questions_import_template.csv');
    }

    /**
     * Импорт аз CSV
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $subjectId = $request->input('subject_id');
        $defaultPoints = (float) Setting::get('test_default_points', 1.0);
        $bankId = $this->getOrCreateDefaultBank($subjectId);
        $rows = $this->parseCsv($request->file('file')->getRealPath());

        if (empty($rows)) {
            return back()->with('error', 'Файл холӣ аст.');
        }

        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                if (empty($row['question_text'])) {
                    $errors[] = "Сатри {$rowNum}: холӣ";
                    continue;
                }

                $type = $row['type'] ?? 'single_choice';
                if (!in_array($type, ['single_choice', 'multiple_choice', 'true_false', 'matching'])) {
                    $type = 'single_choice';
                }

                $question = Question::create([
                    'question_bank_id' => $bankId,
                    'subject_id' => $subjectId,
                    'type' => $type,
                    'question_text' => trim($row['question_text']),
                    'difficulty_level' => min(5, max(1, (int)($row['difficulty_level'] ?? 1))),
                    'points' => $defaultPoints,
                    'explanation' => $row['explanation'] ?? null,
                    'is_active' => true,
                ]);

                if ($type !== 'open_text') {
                    $correctAnswer = strtolower(trim($row['correct_answer'] ?? 'a'));
                    $letters = ['a', 'b', 'c', 'd', 'e'];
                    $fields = ['option_a', 'option_b', 'option_c', 'option_d', 'option_e'];

                    foreach ($fields as $i => $field) {
                        $text = trim($row[$field] ?? '');
                        if (empty($text)) continue;
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $text,
                            'is_correct' => str_contains($correctAnswer, $letters[$i]),
                            'sort_order' => $i,
                        ]);
                    }
                }
                $imported++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Хатогӣ: ' . $e->getMessage());
        }

        return back()->with('success', "{$imported} савол ворид шуд.")->with('import_errors', $errors);
    }

    /**
     * Банки default барои фан (автоматикӣ месозад)
     */
    private function getOrCreateDefaultBank(int $subjectId): int
    {
        $bank = \App\Models\QuestionBank::where('subject_id', $subjectId)->first();
        if ($bank) return $bank->id;

        $subject = Subject::find($subjectId);
        $bank = \App\Models\QuestionBank::create([
            'subject_id' => $subjectId,
            'teacher_id' => auth()->id(),
            'name' => 'Саволҳои ' . ($subject->name ?? 'Фан'),
            'is_active' => true,
        ]);
        return $bank->id;
    }

    /**
     * Таҳлили CSV
     */
    private function parseCsv(string $path): array
    {
        $rows = [];
        $headers = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $lineNum = 0;
            while (($data = fgetcsv($handle, 5000, ',')) !== false) {
                if ($lineNum === 0 && isset($data[0])) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                }
                if ($lineNum === 0) {
                    $headers = array_map('trim', $data);
                    $lineNum++;
                    continue;
                }
                if (count($data) < 2) {
                    $lineNum++;
                    continue;
                }
                $row = [];
                foreach ($headers as $i => $h) {
                    $row[$h] = isset($data[$i]) ? trim($data[$i]) : null;
                }
                if (empty($row['question_text'])) {
                    $lineNum++;
                    continue;
                }
                $rows[] = $row;
                $lineNum++;
            }
            fclose($handle);
        }
        return $rows;
    }
}
