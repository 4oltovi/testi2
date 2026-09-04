<?php

namespace App\Exports;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendanceExport implements FromView, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private Group $group,
        private string $date,
        private array $attendanceData
    ) {}

    public function view(): View
    {
        $students = $this->group->activeStudents()->with('user')->get()->sortBy('user.last_name');

        return view('operator.attendance.export-excel', [
            'group' => $this->group,
            'date' => $this->date,
            'students' => $students,
            'attendanceData' => $this->attendanceData,
        ]);
    }

    public function headings(): array
    {
        return [
            '№',
            'Ному насаб',
            'Коди донишҷӯ',
            'Статус',
        ];
    }

    public function title(): string
    {
        return 'Давомот ' . $this->date;
    }
}
