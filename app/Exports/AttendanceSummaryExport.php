<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendanceSummaryExport implements FromView, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private \Illuminate\Support\Collection $records,
        private string $startDate,
        private string $endDate
    ) {}

    public function view(): View
    {
        return view('operator.attendance.export-summary-excel', [
            'records' => $this->records,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    public function headings(): array
    {
        return [
            '№',
            'Ф.И.О.',
            'Гурӯҳ',
            'Сана',
            'Статус',
            'Соатҳои ғоибӣ',
        ];
    }

    public function title(): string
    {
        return 'Ҳисоботи умумӣ ' . $this->startDate . ' - ' . $this->endDate;
    }
}
