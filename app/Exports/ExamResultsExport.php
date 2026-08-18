<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExamResultsExport implements FromCollection, WithHeadings
{
    public function __construct(private $rows) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Донишҷӯ', 'Гурӯҳ', 'Фан', 'Баҳо', 'Балл'];
    }
}
