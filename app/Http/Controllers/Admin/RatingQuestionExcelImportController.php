<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use App\Services\QuestionExcelParser;

class RatingQuestionExcelImportController extends Controller
{
    private QuestionExcelParser $parser;

    public function __construct()
    {
        $this->parser = new QuestionExcelParser();
    }

    public function importForm(): View
    {
        $subjects = Subject::active()->orderBy('name')->get();
        return view('admin.rating-questions.import', compact('subjects'));
    }

    public function upload(Request $request): RedirectResponse
    {
        if (!$request->hasFile('file')) {
            return back()->with('error', 'Файл бор карда нашудааст.');
        }

        if (!$request->input('subject_id')) {
            return back()->with('error', 'Фан интихоб карда нашудааст.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $file = $request->file('file');
        $subjectId = (int) $request->input('subject_id');

        $questions = $this->parser->parse($file->getRealPath());
        $errors = $this->parser->getErrors();
        $warnings = $this->parser->getWarnings();
        $totalRows = $this->parser->getTotalRows();

        $validQuestions = $questions->filter(fn($q) => empty($q['errors']))->values();
        $invalidQuestions = $questions->filter(fn($q) => !empty($q['errors']))->values();

        $previewData = [
            'subject_id' => $subjectId,
            'total_rows' => $totalRows,
            'simple_count' => $questions->where('type', 'single_choice')->count(),
            'matching_count' => $questions->where('type', 'matching')->count(),
            'valid_count' => $validQuestions->count(),
            'invalid_count' => $invalidQuestions->count(),
            'questions' => $questions->toArray(),
            'errors' => $errors,
            'warnings' => $warnings,
        ];

        Session::put('rating_excel_import_preview', $previewData);

        if (!empty($errors) || !empty($warnings)) {
            Session::put('rating_import_errors', array_merge($errors, $warnings));
        }

        return redirect()->route('admin.rating-questions.import-preview');
    }

    public function preview(): View
    {
        $previewData = Session::get('rating_excel_import_preview');

        if (!$previewData) {
            return redirect()->route('admin.rating-questions.import')
                ->with('error', 'Маълумотҳои пешакӣ эътироф нашуданд. Лутфан файлро аз нав бор кунед.');
        }

        $subject = Subject::find($previewData['subject_id']);
        $questions = collect($previewData['questions']);

        return view('admin.rating-questions.import-preview', [
            'subject' => $subject,
            'totalRows' => $previewData['total_rows'],
            'simpleCount' => $previewData['simple_count'],
            'matchingCount' => $previewData['matching_count'],
            'validCount' => $previewData['valid_count'],
            'invalidCount' => $previewData['invalid_count'],
            'questions' => $questions,
            'importErrors' => collect($previewData['errors']),
            'warnings' => collect($previewData['warnings']),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $previewData = Session::get('rating_excel_import_preview');

        if (!$previewData) {
            return redirect()->route('admin.rating-questions.import')
                ->with('error', 'Маълумотҳои пешакӣ эътироф нашуданд. Лутфан файлро аз нав бор кунед.');
        }

        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $questions = collect($previewData['questions'])->filter(fn($q) => empty($q['errors']));
        $subjectId = $previewData['subject_id'];

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            $bank = \App\Models\QuestionBank::firstOrCreate(
                ['subject_id' => $subjectId, 'bank_type' => 'rating'],
                [
                    'name' => 'Рейтинг: ' . \App\Models\Subject::find($subjectId)->name,
                    'teacher_id' => auth()->id(),
                    'is_active' => true,
                ]
            );

            foreach ($questions as $qData) {
                if ($qData['type'] !== 'single_choice') {
                    $skipped++;
                    continue;
                }

                $question = \App\Models\Question::create([
                    'question_bank_id' => $bank->id,
                    'subject_id' => $subjectId,
                    'type' => 'single_choice',
                    'question_text' => $qData['question_text'],
                    'difficulty_level' => $qData['difficulty_level'] ?? 1,
                    'points' => 2.5,
                    'explanation' => $qData['explanation'] ?? null,
                    'is_active' => true,
                ]);

                foreach ($qData['options'] as $index => $text) {
                    \App\Models\AnswerOption::create([
                        'question_id' => $question->id,
                        'option_text' => $text,
                        'is_correct' => $index === $qData['correct_index'],
                        'sort_order' => $index,
                    ]);
                }

                $imported++;
            }

            DB::commit();
            Session::forget('rating_excel_import_preview');
            Session::forget('rating_import_errors');

            return redirect()->route('admin.rating-questions.index')
                ->with('success', "✅ {$imported} саволи рейтинг импорт карда шуд." . ($skipped ? " ({$skipped} сатр ронда шуд.)" : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Хатогӣ дар вақти воридот: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Саволҳои рейтинг');

        $instructions = [
            ['ИНСТРУКСИЯ:'],
            ['@ = савол', '$ = ҷавоби дуруст', '& = ҷавоби нодуруст'],
            [''],
            ['@Пойтахти Тоҷикистон кадом шаҳр аст?'],
            ['$Душанбе'],
            ['&Хуҷанд'],
            ['&Бохтар'],
            ['&Кӯлоб'],
            [''],
            ['@Миёнаи овоз дар баландии чӣ аст?'],
            ['&Калин'],
            ['$Паст'],
            ['&Миёна'],
            ['&Харош'],
        ];

        $rowNum = 1;
        foreach ($instructions as $row) {
            $sheet->fromArray($row, NULL, 'A' . $rowNum);
            $rowNum++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
        ];

        $sheet->getStyle('A1:A3')->applyFromArray($headerStyle);

        $tmpPath = sys_get_temp_dir() . '/rating_questions_template_' . uniqid() . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download($tmpPath, 'rating_questions_template.xlsx')->deleteFileAfterSend(true);
    }
}
