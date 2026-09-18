<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\TeacherActivityLog;
use App\Models\User;
use App\Services\TeacherExcelParser;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TeacherExcelImportController extends Controller
{
    private TeacherExcelParser $parser;

    public function __construct()
    {
        $this->parser = new TeacherExcelParser();
    }

    public function importForm(): View
    {
        return view('admin.teachers.excel-import');
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Омӯзгорон');

        $headers = [
            'Насаб', 'Ном', 'Номи падар', 'Логин', 'Парол', 'Email', 'Телефон шахсӣ',
            'Рамзи кафедра', 'Рақами кормандӣ', 'Вазифа', 'Дараҷаи илмӣ', 'Унвони илмӣ',
            'Навъи кор', 'Ставка', 'Санаи қабул', 'Анҷоми шартнома', 'Санаи таваллуд',
            'Ҷинс', 'Телефон корӣ', 'Ҳадди аксор ҳафта'
        ];

        foreach ($headers as $col => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }

        $example1 = [
            'Раҳимов', 'Фирдавс', 'Саидович', 'f.rahimov', 'Pass123456',
            'f.rahimov@donishor.tj', '+992 930 123 456', 'FTM', 'EMP-001',
            'Ассистент', 'к.и.т.', 'Доцент', 'full_time', '1.00',
            '2020-09-01', '2025-08-31', '1985-03-15', 'Мард',
            '+992 930 123 456', '36',
        ];
        foreach ($example1 as $col => $value) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue($colLetter . '2', $value);
        }

        $example2 = [
            'Каримова', 'Малика', 'Ғаниевна', 'm.karimova', '',
            'm.karimova@donishor.tj', '+992 930 789 012', 'FIL', 'EMP-002',
            'Доцент', 'д.и.т.', 'Профессор', 'part_time', '0.50',
            '2018-09-01', '', '1980-07-22', 'Зан',
            '+992 930 789 012', '18',
        ];
        foreach ($example2 as $col => $value) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue($colLetter . '3', $value);
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
        ];
        $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);

        $widths = [15, 15, 15, 15, 15, 25, 18, 15, 15, 15, 15, 15, 12, 10, 15, 18, 15, 8, 18, 12];
        foreach ($widths as $col => $width) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        $tmpPath = sys_get_temp_dir() . '/teachers_template_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download($tmpPath, 'teachers_template.xlsx')->deleteFileAfterSend(true);
    }

    public function upload(Request $request): RedirectResponse
    {
        if (!$request->hasFile('file')) {
            return back()->with('error', 'Файл бор карда нашудааст. Лутфан файли Excel-ро интихоб кунед.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $rows = $this->parser->parse($file->getRealPath());
        $errors = $this->parser->getErrors();
        $warnings = $this->parser->getWarnings();
        $totalRows = $this->parser->getTotalRows();

        $validCount = $rows->filter(fn($r) => empty($r['errors']))->count();
        $invalidCount = $rows->filter(fn($r) => !empty($r['errors']))->count();

        $generatedPasswords = $rows->filter(fn($r) => $r['password_was_generated'])->map(fn($r) => [
            'login' => $r['login'],
            'password' => $r['password'],
            'name' => $r['first_name'] . ' ' . $r['last_name'],
        ])->toArray();

        $previewData = [
            'total_rows' => $totalRows,
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'teachers' => $rows->toArray(),
            'errors' => $errors,
            'warnings' => $warnings,
            'generated_passwords' => $generatedPasswords,
        ];

        Session::put('teacher_excel_import_preview', $previewData);

        if (!empty($errors) || !empty($warnings)) {
            Session::put('teacher_import_errors', array_merge($errors, $warnings));
        }

        return redirect()->route('admin.teachers.excel-import-preview');
    }

    public function preview(): View
    {
        $previewData = Session::get('teacher_excel_import_preview');

        if (!$previewData) {
            return redirect()->route('admin.teachers.excel-import')
                ->with('error', 'Маълумотҳои пешакӣ эътироф нашуданд. Лутфан файлро аз нав бор кунед.');
        }

        $teachers = collect($previewData['teachers']);

        return view('admin.teachers.excel-import-preview', [
            'totalRows' => $previewData['total_rows'],
            'validCount' => $previewData['valid_count'],
            'invalidCount' => $previewData['invalid_count'],
            'teachers' => $teachers,
            'importErrors' => collect($previewData['errors']),
            'warnings' => collect($previewData['warnings']),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $previewData = Session::get('teacher_excel_import_preview');

        if (!$previewData) {
            return redirect()->route('admin.teachers.excel-import')
                ->with('error', 'Маълумотҳои пешакӣ эътироф нашуданд. Лутфан файлро аз нав бор кунед.');
        }

        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $teachers = collect($previewData['teachers']);
        $validTeachers = $teachers->filter(fn($t) => empty($t['errors']));

        $imported = 0;
        $failed = 0;
        $generatedPasswords = [];
        $failures = [];

        foreach ($validTeachers as $teacherData) {
            try {
                DB::transaction(function () use ($teacherData, &$generatedPasswords) {
                    $user = User::create([
                        'login' => $teacherData['login'],
                        'email' => $teacherData['email'],
                        'phone' => $teacherData['phone'],
                        'first_name' => $teacherData['first_name'],
                        'last_name' => $teacherData['last_name'],
                        'middle_name' => $teacherData['middle_name'],
                        'password' => Hash::make($teacherData['password']),
                        'status' => 'active',
                    ]);

                    $teacherRole = Role::where('name', 'teacher')->first();
                    $user->roles()->attach($teacherRole->id);

                    $teacher = Teacher::create([
                        'user_id' => $user->id,
                        'department_id' => $teacherData['department_id'],
                        'employee_id' => $teacherData['employee_id'],
                        'position' => $teacherData['position'],
                        'employment_type' => $teacherData['employment_type'],
                        'rate' => $teacherData['rate'],
                        'hire_date' => $teacherData['hire_date'],
                        'contract_end_date' => $teacherData['contract_end_date'],
                        'birth_date' => $teacherData['birth_date'],
                        'gender' => $teacherData['gender'],
                        'academic_degree' => $teacherData['academic_degree'],
                        'academic_title' => $teacherData['academic_title'],
                        'max_hours_per_week' => $teacherData['max_hours_per_week'],
                        'phone_work' => $teacherData['phone_work'],
                        'status' => 'active',
                    ]);

                    TeacherActivityLog::create([
                        'teacher_id' => $teacher->id,
                        'activity_type' => 'hired',
                        'description' => "Ба кор қабул шуд: {$teacherData['position']}",
                        'activity_date' => $teacherData['hire_date'],
                        'created_by' => auth()->id(),
                    ]);

                    AuditLog::log('create', "Омӯзгори нав: {$user->full_name}", Teacher::class, $teacher->id);

                    AuditLog::log('create', "Омӯзгори нав: {$user->full_name}", Teacher::class, $teacher->id);

                    if ($teacherData['password_was_generated']) {
                        $generatedPasswords[] = [
                            'login' => $teacherData['login'],
                            'password' => $teacherData['password'],
                            'name' => $teacherData['first_name'] . ' ' . $teacherData['last_name'],
                        ];
                    }
                });

                $imported++;
            } catch (\Exception $e) {
                $failed++;
                $failures[] = [
                    'row_num' => $teacherData['row_num'],
                    'login' => $teacherData['login'],
                    'name' => $teacherData['first_name'] . ' ' . $teacherData['last_name'],
                    'reason' => $e->getMessage(),
                ];
            }
        }

        Session::forget('teacher_excel_import_preview');

        $resultData = [
            'imported' => $imported,
            'skipped' => $previewData['invalid_count'],
            'failed' => $failed,
            'failures' => $failures,
            'generated_passwords' => $generatedPasswords,
        ];

        return redirect()->route('admin.teachers.excel-import-result')
            ->with('result', $resultData);
    }

    public function result(): View
    {
        $result = session('result');

        if (!$result) {
            return redirect()->route('admin.teachers.index')
                ->with('error', 'Натиҷаи импорт эътироф нашуд.');
        }

        return view('admin.teachers.excel-import-result', [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
            'failures' => $result['failures'] ?? [],
            'generatedPasswords' => $result['generated_passwords'] ?? [],
        ]);
    }
}
