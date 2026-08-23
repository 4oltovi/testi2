<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Illuminate\Support\Collection;

class QuestionExcelParser
{
    private array $errors = [];
    private array $warnings = [];
    private int $totalRows = 0;

    public function parse(string $filePath): Collection
    {
        $this->errors = [];
        $this->warnings = [];
        $this->totalRows = 0;

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (ReaderException $e) {
            $this->errors[] = 'Наметавонистам файли Excel-ро бихонам: ' . $e->getMessage();
            return collect();
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            $this->errors[] = 'Файл холӣ аст.';
            return collect();
        }

        $this->totalRows = count($rows);
        $questions = $this->parseMarkerFormat($rows);

        return $questions;
    }

    private function parseMarkerFormat(array $rows): Collection
    {
        $questions = collect();
        $currentQuestion = null;
        $questionStartRow = 0;

        foreach ($rows as $rowIndex => $row) {
            $rowNum = $rowIndex + 1;
            $cellValue = trim((string)($row[0] ?? ''));

            if ($cellValue === '') {
                if ($currentQuestion !== null) {
                    $this->finalizeQuestion($currentQuestion, $questionStartRow);
                    $questions->push($currentQuestion);
                    $currentQuestion = null;
                }
                continue;
            }

            $firstChar = mb_substr($cellValue, 0, 1);
            $text = mb_substr($cellValue, 1);

            if ($firstChar === '@') {
                if ($currentQuestion !== null) {
                    $this->finalizeQuestion($currentQuestion, $questionStartRow);
                    $questions->push($currentQuestion);
                }

                $currentQuestion = [
                    'row_num' => $rowNum,
                    'type' => 'single_choice',
                    'question_text' => $text,
                    'difficulty_level' => 1,
                    'explanation' => '',
                    'options' => [],
                    'correct_index' => null,
                    'errors' => [],
                ];
                $questionStartRow = $rowNum;
            } elseif ($firstChar === '$') {
                if ($currentQuestion === null) {
                    $this->errors[] = "Сатри {$rowNum}: Савол бо аломати @ оғоз нашудааст.";
                    continue;
                }

                if ($text === '') {
                    $currentQuestion['errors'][] = "Сатри {$rowNum}: Матни ҷавоби дуруст холӣ аст.";
                    $this->errors[] = "Сатри {$rowNum}: Матни ҷавоби дуруст холӣ аст.";
                    continue;
                }

                if ($currentQuestion['correct_index'] !== null) {
                    $currentQuestion['errors'][] = 'Савол зиёда аз як ҷавоби дуруст дорад.';
                    $this->errors[] = "Сатри {$rowNum}: Савол зиёда аз як ҷавоби дуруст дорад.";
                    continue;
                }

                $currentQuestion['correct_index'] = count($currentQuestion['options']);
                $currentQuestion['options'][] = $text;
            } elseif ($firstChar === '&') {
                if ($currentQuestion === null) {
                    $this->errors[] = "Сатри {$rowNum}: Савол бо аломати @ оғоз нашудааст.";
                    continue;
                }

                if ($text === '') {
                    $currentQuestion['errors'][] = "Сатри {$rowNum}: Матни варианти ҷавоб холӣ аст.";
                    $this->errors[] = "Сатри {$rowNum}: Матни варианти ҷавоб холӣ аст.";
                    continue;
                }

                $currentQuestion['options'][] = $text;
            } else {
                $this->warnings[] = "Сатри {$rowNum}: Аломати номаълум '{$firstChar}' рад карда шуд.";
            }
        }

        if ($currentQuestion !== null) {
            $this->finalizeQuestion($currentQuestion, $questionStartRow);
            $questions->push($currentQuestion);
        }

        return $questions;
    }

    private function finalizeQuestion(array &$question, int $startRow): void
    {
        if (empty($question['question_text'])) {
            $question['errors'][] = 'Матни савол холӣ аст.';
            $this->errors[] = "Сатри {$startRow}: Матни савол холӣ аст.";
        }

        if ($question['correct_index'] === null) {
            $question['errors'][] = 'Ҷавоби дуруст бо аломати $ муайян нашудааст.';
            $this->errors[] = "Сатри {$startRow}: Ҷавоби дуруст бо аломати $ муайян нашудааст.";
        }

        if (count($question['options']) < 2) {
            $question['errors'][] = 'Ҳадди ақал 2 вариант лозим аст (1 ҷавоби дуруст + 1 ҷавоби дигар).';
            $this->errors[] = "Сатри {$startRow}: Ҳадди ақал 2 вариант лозим аст.";
        }

        $duplicates = $this->findDuplicateOptions($question['options']);
        if (!empty($duplicates)) {
            $question['warnings'][] = 'Вариантҳои такроршаванда: ' . implode(', ', $duplicates);
            $this->warnings[] = "Сатри {$startRow}: Вариантҳои такроршаванда: " . implode(', ', $duplicates);
        }
    }

    private function findDuplicateOptions(array $options): array
    {
        $duplicates = [];
        $seen = [];
        foreach ($options as $opt) {
            $lower = mb_strtolower(trim($opt));
            if (isset($seen[$lower])) {
                $duplicates[] = $opt;
            }
            $seen[$lower] = true;
        }
        return array_unique($duplicates);
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
