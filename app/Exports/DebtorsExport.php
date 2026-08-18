<?php

namespace App\Exports;

use App\Models\AcademicDebt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DebtorsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private $debts) {}

    public function collection()
    {
        return $this->debts;
    }

    public function headings(): array
    {
        return ['ID', 'Донишҷӯ', 'Гурӯҳ', 'Фан', 'Семестр', 'Сабаб', 'Баҳо', 'Санаи қарз'];
    }

    public function map($debt): array
    {
        return [
            $debt->id,
            $debt->student?->user?->full_name ?? '-',
            $debt->student?->group?->name ?? '-',
            $debt->subject?->name ?? '-',
            $debt->semester?->name ?? '-',
            $debt->reason ?? '-',
            $debt->original_grade ?? '-',
            $debt->debt_date?->format('Y-m-d') ?? '-',
        ];
    }
}
