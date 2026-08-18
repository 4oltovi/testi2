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
    public function importForm(): View
    {
        $subjects = Subject::active()->orderBy('name')->get();

        return view('admin.rating-questions.import', compact('subjects'));
    }

    /**
     * Импорти CSV барои саволномаи рейтинг
     */
    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'subject_id.required' => 'Фанро интихоб кунед.',
            'file.required' => 'Файли CSV-ро боргузорӣ кунед.',
            'file.mimes' => 'Танҳо файлҳои CSV ё TXT қабул мешаванд.',
        ]);

        $subjectId = (int) $validated['subject_id'];
        $file = $request->file('file');
        $subject = Subject::findOrFail($subjectId);

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Файл кушода намешавад.');
        }

        $header = fgetcsv($handle, 5000, ',');
        if ($header === false || !in_array('question_text', $header) || !in_array('correct', $header)) {
            fclose($handle);
            return back()->with('error', 'Шакли файл нодуруст аст. Сатри сарлавҳа бояд дорад: question_text, options, correct, difficulty_level, explanation.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($handle, $header, $subjectId, $subject, &$imported, &$skipped, &$errors) {
            $bank = QuestionBank::firstOrCreate(
                ['subject_id' => $subjectId, 'bank_type' => 'rating'],
                [
                    'name' => 'Рейтинг: ' . $subject->name,
                    'teacher_id' => auth()->id(),
                    'is_active' => true,
                ]
            );

            while (($row = fgetcsv($handle, 5000, ',')) !== false) {
                if (count($row) < 5) {
                    $skipped++;
                    continue;
                }

                $data = array_combine($header, $row);
                if ($data === false) {
                    $skipped++;
                    continue;
                }

                $questionText = trim($data['question_text'] ?? '');
                $optionsRaw = trim($data['options'] ?? '');
                $correctIndex = (int) ($data['correct'] ?? 0);
                $difficulty = (int) ($data['difficulty_level'] ?? 1);
                $explanation = trim($data['explanation'] ?? '');

                if ($questionText === '' || $optionsRaw === '') {
                    $skipped++;
                    continue;
                }

                $options = array_map('trim', explode('|', $optionsRaw));
                $options = array_slice($options, 0, 4);
                while (count($options) < 4) {
                    $options[] = 'Варианти ' . (count($options) + 1);
                }

                if ($correctIndex < 0 || $correctIndex > 3) {
                    $correctIndex = 0;
                }

                $difficulty = max(1, min(3, $difficulty));

                $question = Question::create([
                    'question_bank_id' => $bank->id,
                    'subject_id' => $subjectId,
                    'type' => 'single_choice',
                    'question_text' => $questionText,
                    'difficulty_level' => $difficulty,
                    'points' => 2.5,
                    'explanation' => $explanation ?: null,
                    'is_active' => true,
                ]);

                foreach ($options as $i => $text) {
                    AnswerOption::create([
                        'question_id' => $question->id,
                        'option_text' => $text ?: 'Варианти ' . ($i + 1),
                        'is_correct' => $i === $correctIndex,
                        'sort_order' => $i + 1,
                    ]);
                }

                $imported++;
            }
        });

        fclose($handle);

        if ($imported > 0) {
            return back()->with('success', "✅ {$imported} савол импорт карда шуд." . ($skipped ? " ({$skipped} сатр ронда шуд.)" : ''));
        }

        return back()->with('error', 'Ягон савол импорт карда нашудааст. Лутфан файлро санҷед.');
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
     * Зерфармоии шаблони CSV
     */
    public function downloadTemplate()
    {
        $path = storage_path('app/templates/rating_questions_import_template.csv');

        return response()->download($path, 'rating_questions_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}