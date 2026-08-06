<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    /**
     * Саҳифаи импорт
     */
    public function index(): View
    {
        $groups = Group::with(['specialty', 'course'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.import.index', compact('groups'));
    }

    /**
     * Зеркашии шаблони Excel
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $templatePath = storage_path('app/templates/students_import_template.xlsx');

        // Агар шаблон нест — CSV месозем
        if (!file_exists($templatePath)) {
            $csvPath = storage_path('app/templates/students_import_template.csv');

            if (!is_dir(dirname($csvPath))) {
                mkdir(dirname($csvPath), 0755, true);
            }

            $headers = [
                'last_name',       // Насаб
                'first_name',      // Ном
                'middle_name',     // Номи падар
                'email',           // Email
                'phone',           // Телефон
                'birth_date',      // Санаи таваллуд (YYYY-MM-DD)
                'gender',          // Ҷинс (male/female)
                'student_id_number', // Рақами донишҷӯ
                'passport_number', // Рақами паспорт
            ];

            $example = [
                'Раҳимов',
                'Аҳмад',
                'Саидович',
                'ahmad@example.com',
                '+992901234567',
                '2003-05-15',
                'male',
                'ST-2024-001',
                'AA1234567',
            ];

            $file = fopen($csvPath, 'w');
            // BOM для UTF-8 в Excel
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers);
            fputcsv($file, $example);
            fclose($file);

            return response()->download($csvPath, 'students_import_template.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return response()->download($templatePath, 'students_import_template.xlsx');
    }

    /**
     * Импорти донишҷӯён аз файли Excel/CSV
     */
    public function importStudents(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120', // Max 5MB
            'group_id' => 'required|exists:groups,id',
            'generate_password' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $groupId = $request->input('group_id');
        $generatePassword = $request->boolean('generate_password', true);

        // Хондани файл
        $rows = $this->parseFile($file);

        if (empty($rows)) {
            return back()->with('error', 'Файл холӣ аст ё формати нодуруст дорад.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +1 header, +1 zero-index

                // Валидатсия
                $validator = Validator::make($row, [
                    'last_name' => 'required|string|max:100',
                    'first_name' => 'required|string|max:100',
                    'middle_name' => 'nullable|string|max:100',
                    'email' => 'nullable|email|max:150',
                    'phone' => 'nullable|string|max:20',
                    'birth_date' => 'nullable|date',
                    'gender' => 'nullable|in:male,female',
                    'student_id_number' => 'nullable|string|max:50',
                    'passport_number' => 'nullable|string|max:20',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Сатри {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    $skipped++;
                    continue;
                }

                // Санҷиш: такрорӣ?
                if (!empty($row['email'])) {
                    $existing = User::where('email', $row['email'])->first();
                    if ($existing) {
                        $errors[] = "Сатри {$rowNumber}: Email «{$row['email']}» аллакай мавҷуд аст.";
                        $skipped++;
                        continue;
                    }
                }

                if (!empty($row['student_id_number'])) {
                    $existingStudent = Student::where('student_id_number', $row['student_id_number'])->first();
                    if ($existingStudent) {
                        $errors[] = "Сатри {$rowNumber}: Рақами «{$row['student_id_number']}» аллакай мавҷуд аст.";
                        $skipped++;
                        continue;
                    }
                }

                // Email автоматикӣ месозем агар нест
                $email = $row['email'] ?? null;
                if (empty($email)) {
                    $email = Str::slug($row['first_name'] . '.' . $row['last_name']) . '.' . Str::random(4) . '@student.donishor.tj';
                }

                // Парол
                $password = $generatePassword
                    ? Str::random(8)
                    : 'student123';

                // User сохтан
                $user = User::create([
                    'first_name' => trim($row['first_name']),
                    'last_name' => trim($row['last_name']),
                    'middle_name' => trim($row['middle_name'] ?? ''),
                    'email' => $email,
                    'phone' => $row['phone'] ?? null,
                    'password' => Hash::make($password),
                    'is_active' => true,
                ]);

                // Role
                $user->roles()->attach(
                    \App\Models\Role::where('slug', 'student')->first()?->id
                );

                // Student сохтан
                $group = Group::find($groupId);

                Student::create([
                    'user_id' => $user->id,
                    'group_id' => $groupId,
                    'student_id_number' => $row['student_id_number'] ?? ('ST-' . date('Y') . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT)),
                    'course_id' => $group->course_id ?? null,
                    'specialty_id' => $group->specialty_id ?? null,
                    'enrollment_date' => now(),
                    'status' => 'active',
                    'birth_date' => $row['birth_date'] ?? null,
                    'gender' => $row['gender'] ?? null,
                    'passport_number' => $row['passport_number'] ?? null,
                ]);

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Хатогии система: ' . $e->getMessage());
        }

        // Натиҷа
        $message = "{$imported} донишҷӯ бо муваффақият ворид шуд.";
        if ($skipped > 0) {
            $message .= " {$skipped} сатр гузаронида шуд.";
        }

        return back()
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    /**
     * Parse файли CSV/Excel ба массив
     */
    private function parseFile($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($path);
        }

        // Барои xlsx — аз csv fallback
        // Дар production PhpSpreadsheet/Maatwebsite Excel истифода мешавад
        // Ин ҷо CSV parser истифода мекунем
        return $this->parseCsv($path);
    }

    /**
     * Parse CSV
     */
    private function parseCsv(string $path): array
    {
        $rows = [];
        $headers = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $lineNum = 0;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // BOM removal
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
                foreach ($headers as $i => $header) {
                    $row[$header] = isset($data[$i]) ? trim($data[$i]) : null;
                }

                // Сатри холиро гузаронем
                if (empty($row['first_name']) && empty($row['last_name'])) {
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
