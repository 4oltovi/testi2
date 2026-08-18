<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private $students) {}

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return ['ID', 'Ному насаб', 'Гурӯҳ', 'Курс', 'Таъсис'];
    }

    public function map($student): array
    {
        return [
            $student->id,
            $student->user?->full_name ?? '-',
            $student->group?->name ?? '-',
            $student->course?->number ?? $student->course?->name ?? '-',
            $student->specialty?->department?->faculty?->name ?? $student->specialty?->name ?? '-',
        ];
    }
}
