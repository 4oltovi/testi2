<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Illuminate\Support\Collection;
use App\Models\Department;
use App\Models\User;
use App\Models\Teacher;

class TeacherExcelParser
{
    private array $errors = [];
    private array $warnings = [];
    private int $totalRows = 0;
    private array $seenLogins = [];
    private array $seenEmployeeIds = [];

    public function parse(string $filePath): Collection
    {
        $this->errors = [];
        $this->warnings = [];
        $this->totalRows = 0;
        $this->seenLogins = [];
        $this->seenEmployeeIds = [];

        $rows = $this->loadSpreadsheet($filePath);
        if ($rows === null) {
            return collect();
        }

        if (empty($rows)) {
            $this->errors[] = 'Файл холӣ аст.';
            return collect();
        }

        array_shift($rows);

        $this->totalRows = count($rows);
        $teachers = $this->parseRows($rows);

        return $teachers;
    }

    private function loadSpreadsheet(string $filePath): ?array
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (ReaderException $e) {
            $this->errors[] = 'Наметавонистам файли Excel-ро бихонам: ' . $e->getMessage();
            return null;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        return $rows;
    }

    private function parseRows(array $rows): Collection
    {
        $teachers = collect();

        foreach ($rows as $rowIndex => $row) {
            $rowNum = $rowIndex + 2;

            $lastName   = trim((string)($row[0] ?? ''));
            $firstName  = trim((string)($row[1] ?? ''));
            $middleName = trim((string)($row[2] ?? ''));
            $login      = trim((string)($row[3] ?? ''));
            $password   = trim((string)($row[4] ?? ''));
            $email      = trim((string)($row[5] ?? ''));
            $phone      = trim((string)($row[6] ?? ''));
            $deptCode   = trim((string)($row[7] ?? ''));
            $employeeId = trim((string)($row[8] ?? ''));
            $position   = trim((string)($row[9] ?? ''));
            $degree     = trim((string)($row[10] ?? ''));
            $title      = trim((string)($row[11] ?? ''));
            $empType    = trim((string)($row[12] ?? ''));
            $rate       = trim((string)($row[13] ?? ''));
            $hireDate   = trim((string)($row[14] ?? ''));
            $contractEnd= trim((string)($row[15] ?? ''));
            $birthDate  = trim((string)($row[16] ?? ''));
            $gender     = trim((string)($row[17] ?? ''));
            $phoneWork  = trim((string)($row[18] ?? ''));
            $maxHours   = trim((string)($row[19] ?? ''));

            $errors = [];
            $warnings = [];

            if ($lastName === '') {
                $errors[] = "Сатри {$rowNum}: Насаб ҳатмӣ аст.";
            }
            if ($firstName === '') {
                $errors[] = "Сатри {$rowNum}: Ном ҳатмӣ аст.";
            }
            if ($login === '') {
                $errors[] = "Сатри {$rowNum}: Логин ҳатмӣ аст.";
            }
            if ($employeeId === '') {
                $errors[] = "Сатри {$rowNum}: Рақами кормандӣ ҳатмӣ аст.";
            }
            if ($deptCode === '') {
                $errors[] = "Сатри {$rowNum}: Рамзи кафедра ҳатмӣ аст.";
            }
            if ($position === '') {
                $errors[] = "Сатри {$rowNum}: Вазифа ҳатмӣ аст.";
            }
            if ($hireDate === '') {
                $errors[] = "Сатри {$rowNum}: Санаи қабул ҳатмӣ аст.";
            }

            if ($login !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $login)) {
                $errors[] = "Сатри {$rowNum}: Логин танҳо алифбо, равшана ва нишонаи -_ дорад.";
            }

            if ($login !== '') {
                $loginLower = strtolower($login);
                if (isset($this->seenLogins[$loginLower])) {
                    $errors[] = "Сатри {$rowNum}: Дупликацияи логин «{$login}» (дар сатри {$this->seenLogins[$loginLower]} такрор шудааст).";
                } else {
                    $this->seenLogins[$loginLower] = $rowNum;
                }
            }

            if ($employeeId !== '') {
                if (isset($this->seenEmployeeIds[$employeeId])) {
                    $errors[] = "Сатри {$rowNum}: Дупликацияи рақами кормандӣ «{$employeeId}» (дар сатри {$this->seenEmployeeIds[$employeeId]} такрор шудааст).";
                } else {
                    $this->seenEmployeeIds[$employeeId] = $rowNum;
                }
            }

            if ($login !== '' && User::where('login', $login)->exists()) {
                $errors[] = "Сатри {$rowNum}: Логин «{$login}» аллакай вазъият дорад.";
            }
            if ($employeeId !== '' && Teacher::where('employee_id', $employeeId)->exists()) {
                $errors[] = "Сатри {$rowNum}: Рақами кормандӣ «{$employeeId}» аллакай вазъият дорад.";
            }

            $departmentId = null;
            if ($deptCode !== '') {
                $department = Department::where('code', $deptCode)->first();
                if ($department) {
                    $departmentId = $department->id;
                } else {
                    $errors[] = "Сатри {$rowNum}: Кафедраи «{$deptCode}» ёфт нашуд.";
                }
            }

            $knownPositions = ['Ассистент', 'Муаллими калон', 'Доцент', 'Профессор'];
            if ($position !== '' && !in_array($position, $knownPositions, true)) {
                $warnings[] = "Сатри {$rowNum}: Вазифа «{$position}» маъруф надорад — бо вуҷуд он сабт мешавад.";
            }

            $employmentType = $this->normalizeEmploymentType($empType, $warnings, $rowNum);

            $rateValue = 1.00;
            if ($rate !== '') {
                if (is_numeric($rate)) {
                    $rateValue = (float) $rate;
                    if ($rateValue < 0.25 || $rateValue > 2.0) {
                        $errors[] = "Сатри {$rowNum}: Ставка бояд аз 0.25 то 2.0 бошад.";
                    }
                } else {
                    $errors[] = "Сатри {$rowNum}: Ставка равшан надорад.";
                }
            }

            $hireDateParsed = $this->parseDate($hireDate);
            if ($hireDate !== '' && $hireDateParsed === null) {
                $errors[] = "Сатри {$rowNum}: Санаи қабул ({$hireDate}) формати дуруст надорад.";
            }

            $contractEndParsed = $this->parseDate($contractEnd);
            if ($contractEnd !== '' && $contractEndParsed === null) {
                $errors[] = "Сатри {$rowNum}: Анҷоми шартнома ({$contractEnd}) формати дуруст надорад.";
            }

            $birthDateParsed = $this->parseDate($birthDate);
            if ($birthDate !== '' && $birthDateParsed === null) {
                $errors[] = "Сатри {$rowNum}: Санаи таваллуд ({$birthDate}) формати дуруст надорад.";
            }

            if ($hireDateParsed && $contractEndParsed && $contractEndParsed <= $hireDateParsed) {
                $errors[] = "Сатри {$rowNum}: Анҷоми шартнома бояд баъди санаи қабул бошад.";
            }

            if ($birthDateParsed && $birthDateParsed >= today()) {
                $errors[] = "Сатри {$rowNum}: Санаи таваллуд бояд пеш аз имруз бошад.";
            }

            $genderNormalized = $this->normalizeGender($gender);

            $maxHoursValue = 36;
            if ($maxHours !== '') {
                if (is_numeric($maxHours)) {
                    $maxHoursValue = (int) $maxHours;
                    if ($maxHoursValue < 4 || $maxHoursValue > 72) {
                        $errors[] = "Сатри {$rowNum}: Ҳадди аксор дар ҳафта бояд аз 4 то 72 бошад.";
                    }
                } else {
                    $errors[] = "Сатри {$rowNum}: Ҳадди аксор равшан надорад.";
                }
            }

            $passwordWasGenerated = false;
            if ($password === '') {
                $password = $this->generatePassword();
                $passwordWasGenerated = true;
            }

            $teachers->push([
                'row_num' => $rowNum,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'login' => $login,
                'password' => $password,
                'password_was_generated' => $passwordWasGenerated,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'department_code' => $deptCode,
                'department_id' => $departmentId,
                'employee_id' => $employeeId,
                'position' => $position,
                'academic_degree' => $degree !== '' ? $degree : null,
                'academic_title' => $title !== '' ? $title : null,
                'employment_type' => $employmentType,
                'rate' => $rateValue,
                'hire_date' => $hireDateParsed,
                'contract_end_date' => $contractEndParsed,
                'birth_date' => $birthDateParsed,
                'gender' => $genderNormalized,
                'phone_work' => $phoneWork !== '' ? $phoneWork : null,
                'max_hours_per_week' => $maxHoursValue,
                'errors' => $errors,
                'warnings' => $warnings,
            ]);
        }

        return $teachers;
    }

    private function normalizeEmploymentType(string $value, array &$warnings, int $rowNum): string
    {
        $mapping = [
            'full_time' => 'full_time',
            'доимӣ' => 'full_time',
            'part_time' => 'part_time',
            'нимшатота' => 'part_time',
            'hourly' => 'hourly',
            'соатбайъ' => 'hourly',
        ];

        $lower = mb_strtolower(trim($value));
        if (isset($mapping[$lower])) {
            return $mapping[$lower];
        }

        $direct = ['full_time', 'part_time', 'hourly'];
        if (in_array(strtolower($value), $direct)) {
            return strtolower($value);
        }

        if ($value !== '') {
            $warnings[] = "Сатри {$rowNum}: Навъи кор «{$value}» маъруф надорад — default full_time истифода мешавад.";
        }

        return 'full_time';
    }

    private function normalizeGender(string $value): ?string
    {
        $mapping = [
            'мард' => 'male',
            'male' => 'male',
            'зан' => 'female',
            'female' => 'female',
        ];

        $lower = mb_strtolower(trim($value));
        return $mapping[$lower] ?? ($value !== '' ? $value : null);
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            if ($d) return $d->format('Y-m-d');
        }

        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $value)) {
            $d = \DateTime::createFromFormat('d.m.Y', $value);
            if ($d) return $d->format('Y-m-d');
        }

        $ts = strtotime($value);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    private function generatePassword(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }
}
