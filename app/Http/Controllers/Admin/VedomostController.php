<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Models\Vedomost;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use App\Models\Setting;

class VedomostController extends Controller
{
    // ===================== САҲИФАИ АСОСӢ =====================
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('id', 'desc')->get();
        $semesters     = Semester::orderBy('id')->get();
        $groups        = Group::orderBy('name')->get();

        $groupId    = $request->input('group_id');
        $semesterId = $request->input('semester_id');
        $yearId     = $request->input('academic_year_id');

        $vedomosts = collect();

        if ($groupId && $semesterId) {
            $assignments = SubjectAssignment::with(['subject', 'teacher', 'group'])
                ->where('group_id', $groupId)
                ->where('semester_id', $semesterId)
                ->get();

            $ids = $assignments->map(function ($a) use ($yearId) {
                return Vedomost::firstOrCreate(
                    ['subject_assignment_id' => $a->id, 'semester_id' => $a->semester_id],
                    [
                        'group_id'         => $a->group_id,
                        'subject_id'       => $a->subject_id,  // ← ИСЛОҲ ШУД
                        'teacher_id'       => $a->teacher_id,
                        'academic_year_id' => $yearId ?: $a->group?->academic_year_id,
                        'status'           => 'draft',
                    ]
                )->id;
            });

            $vedomosts = Vedomost::with(['subject', 'group', 'teacher', 'semester', 'academicYear'])
                ->whereIn('id', $ids)
                ->get()
                ->sortBy(fn($v) => mb_strtolower($v->subject->name ?? ''))
                ->values();
        }

        return view('admin.vedomosts.index', compact(
            'academicYears',
            'semesters',
            'groups',
            'vedomosts',
            'groupId',
            'semesterId',
            'yearId'
        ));
    }

    // ===================== НОМИ ДОНИШҶӮ =====================
    protected function studentName(Student $student): string
    {
        // 1. Аввал кӯшиш мекунем, ки номро аз user гирем
        if ($student->user) {
            // full_name - ин accessor, ки шумо аллакай доред
            if (!empty($student->user->full_name)) {
                return $student->user->full_name;
            }

            // Агар accessor кор накунад, дастӣ месозем
            $fio = trim(
                ($student->user->last_name ?? '') . ' ' .
                    ($student->user->first_name ?? '') . ' ' .
                    ($student->user->middle_name ?? '')
            );

            if (!empty($fio)) {
                return $fio;
            }
        }

        // 2. Агар user набошад ё ном надошта бошад
        return 'Донишҷӯ #' . ($student->student_id_number ?? $student->id);
    }
    // ===================== МАЪЛУМОТИ ВЕДОМОСТ =====================
    protected function buildVedomostData(Vedomost $vedomost): array
    {
        $grades = SemesterGrade::where('subject_assignment_id', $vedomost->subject_assignment_id)
            ->where('semester_id', $vedomost->semester_id)
            ->get()->keyBy('student_id');

        $students = Student::with('user')
            ->where('group_id', $vedomost->group_id)
            ->where('status', 'active')
            ->get()
            ->sortBy(fn($s) => mb_strtolower($this->studentName($s)))
            ->values();

        $rows = $students->map(function ($s, $i) use ($grades) {
            $g    = $grades->get($s->id);
            $exam = (float) ($g?->exam_score ?? 0);
            $r1   = (float) ($g?->rating1_score ?? 0);
            $r2   = (float) ($g?->rating2_score ?? 0);

            $total = $g?->total_score ?? round((($r1 + $r2) / 2) * 0.5 + ($exam * 0.5), 2);
            $point = (float) ($g?->grade_point ?? 0);
            $cred  = (int) ($g?->credits_earned ?? 0);

            return [
                'n'        => $i + 1,
                'code'     => $s->student_id_number ?? $s->id,
                'fio'      => $this->studentName($s),
                'r1'       => number_format($r1, 2),
                'r2'       => number_format($r2, 2),
                'examComp' => number_format($exam * 0.5, 2),
                'total'    => $total,
                'letter'   => $g?->letter_grade ?? '-',
                'point'    => number_format($point, 2),
                'cred'     => $cred,
                'ball'     => number_format($point * $cred, 2),
            ];
        });
        // Санаи имтиҳон аз журнал (semester_grades.exam_date)
        $examDate = $grades->first(fn($g) => $g->exam_date)?->exam_date;

        return [
            'v'               => $vedomost,
            'rows'            => $rows,
            'examDate'        => $examDate,
            'institutionName' => Setting::get('institution_name', 'Муассисаи ғайридавлатии коллеҷи тиббии "Даво" Маркази тестӣ'),
            'deputyDirector'  => Setting::get('deputy_director_name', 'Гулов М.'),
            'centerHead'      => Setting::get('testing_center_head_name', 'Хоҷаев М.М.'),
        ];
        return ['v' => $vedomost, 'rows' => $rows];
    }

    // ===================== PDF-И ЯК ВЕДОМОСТ =====================
    public function downloadPdf(Vedomost $vedomost)
    {
        $data = $this->buildVedomostData($vedomost);
        $pdf  = Pdf::loadView('admin.vedomosts.pdf', $data);
        $pdf->setPaper('a4');

        $name = 'vedomost_' . ($vedomost->group->name ?? 'group') . '_' . ($vedomost->subject->name ?? 'fan') . '.pdf';

        return $pdf->download($name);
    }

    // ===================== ДИДАНИ ВЕДОМОСТ ДАР БРАУЗЕР =====================
    public function preview(Vedomost $vedomost)
    {
        $data = $this->buildVedomostData($vedomost);
        $pdf  = Pdf::loadView('admin.vedomosts.pdf', $data);
        $pdf->setPaper('a4');

        return $pdf->stream('vedomost_' . $vedomost->id . '.pdf');
    }

    // ===================== ZIP-И ҲАМА ВЕДОМОСТҲО =====================
    public function downloadZip(Request $request)
    {
        $vedomosts = Vedomost::with(['subject', 'group', 'teacher', 'semester', 'academicYear'])
            ->where('group_id', $request->input('group_id'))
            ->where('semester_id', $request->input('semester_id'))
            ->get()
            ->sortBy(fn($v) => mb_strtolower($v->subject->name ?? ''))
            ->values();

        if ($vedomosts->isEmpty()) {
            return back()->with('error', 'Ведомост ёфт нашуд!');
        }

        $zipPath = storage_path('app/vedomosts_' . time() . '.zip');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($vedomosts as $i => $v) {
            $data = $this->buildVedomostData($v);
            $pdf  = Pdf::loadView('admin.vedomosts.pdf', $data);
            $pdf->setPaper('a4');

            $file = sprintf(
                '%02d_%s.pdf',
                $i + 1,
                str_replace(['/', '\\', ' ', '"', "'"], ['-', '-', '_', '', ''], $v->subject->name ?? 'fan')
            );

            $zip->addFromString($file, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
