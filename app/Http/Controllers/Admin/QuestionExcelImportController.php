<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\AnswerOption;
use App\Services\QuestionExcelParser;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class QuestionExcelImportController extends Controller
{
    private QuestionExcelParser $parser;

    public function __construct()
    {
        $this->parser = new QuestionExcelParser();
    }

    public function importForm(): View
    {
        $subjects = Subject::orderBy('name')->get();
        return view('admin.questions.excel-import', compact('subjects'));
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Саволҳо');

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
            ['@2 + 2 = ?'],
            ['&3'],
            ['$4'],
            ['&5'],
            ['&6'],
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
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
        ];

        $sheet->getStyle('A1:A3')->applyFromArray($headerStyle);

        $tmpPath = sys_get_temp_dir() . '/questions_template_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download($tmpPath, 'questions_template.xlsx')->deleteFileAfterSend(true);
    }

    public function upload(Request $request): RedirectResponse
    {
        \Log::info('Excel import upload attempt', [
            'has_file' => $request->hasFile('file'),
            'file_valid' => $request->file('file')?->isValid(),
            'file_error' => $request->file('file')?->getError() ?? 'no file',
            'subject_id' => $request->input('subject_id'),
            'all_input' => $request->except('file'),
            'content_type' => $request->headers->get('Content-Type'),
        ]);

        if (!$request->hasFile('file')) {
            return back()->with('error', 'Файл бор карда нашудааст. Лутфан файли Excel-ро интихоб кунед.');
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

        Session::put('excel_import_preview', $previewData);

        if (!empty($errors) || !empty($warnings)) {
            Session::put('import_errors', array_merge($errors, $warnings));
        }

        return redirect()->route('admin.questions.excel-import-preview');
    }

    public function preview(): View
    {
        $previewData = Session::get('excel_import_preview');

        if (!$previewData) {
            return redirect()->route('admin.questions.excel-import')
                ->with('error', 'Маълумотҳои пешакӣ эътироф нашуданд. Лутфан файлро аз нав бор кунед.');
        }

        $subject = Subject::find($previewData['subject_id']);
        $questions = collect($previewData['questions']);

        return view('admin.questions.excel-import-preview', [
            'cacheKey' => 'session',
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
        $previewData = Session::get('excel_import_preview');

        if (!$previewData) {
            return redirect()->route('admin.questions.excel-import')
                ->with('error', 'Маълумотҳои пешакӣ эътироф нашуданд. Лутфан файлро аз нав бор кунед.');
        }

        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $questions = collect($previewData['questions'])->filter(fn($q) => empty($q['errors']));
        $subjectId = $previewData['subject_id'];
        $bankId = $this->getOrCreateDefaultBank($subjectId);

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($questions as $qData) {
                $question = Question::create([
                    'question_bank_id' => $bankId,
                    'subject_id' => $subjectId,
                    'type' => $qData['type'],
                    'question_text' => $qData['question_text'],
                    'difficulty_level' => $qData['difficulty_level'],
                    'points' => $qData['type'] === 'matching' ? 10.0 : 2.5,
                    'explanation' => $qData['explanation'] ?? null,
                    'is_active' => true,
                ]);

                if ($qData['type'] === 'matching') {
                    foreach ($qData['pairs'] as $index => $pair) {
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $pair['item'] . '|||' . ($pair['match'] ?? ''),
                            'is_correct' => true,
                            'sort_order' => $index,
                        ]);
                    }

                    foreach ($qData['extra_options'] as $index => $extra) {
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $extra['text'],
                            'is_correct' => false,
                            'sort_order' => 100 + $index,
                        ]);
                    }
                } else {
                    foreach ($qData['options'] as $index => $text) {
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $text,
                            'is_correct' => $index === $qData['correct_index'],
                            'sort_order' => $index,
                        ]);
                    }
                }

                $imported++;
            }

            DB::commit();
            Session::forget('excel_import_preview');

            return redirect()->route('admin.exams.questions.index')
                ->with('success', "{$imported} савол бо муваффақият ворид шуд.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Хатогӣ дар вақти воридот: ' . $e->getMessage());
        }
    }

    private function getOrCreateDefaultBank(int $subjectId): int
    {
        $bank = QuestionBank::where('subject_id', $subjectId)
            ->where('bank_type', 'exam')
            ->first();

        if ($bank) return $bank->id;

        $subject = Subject::find($subjectId);
        $bank = QuestionBank::create([
            'subject_id' => $subjectId,
            'teacher_id' => auth()->id(),
            'name' => 'Саволҳои ' . ($subject->name ?? 'Фан'),
            'bank_type' => 'exam',
            'is_active' => true,
        ]);

        return $bank->id;
    }
}
