<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GradeScale;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Group;
use App\Models\RetakeExam;
use App\Models\RetakeExamAttempt;
use App\Models\RetakeExamStudent;
use App\Models\RetakeVedomost;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Models\Vedomost;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use ZipArchive;

class VedomostController extends Controller
{
    // ===================== САҲИФАИ АСОСӢ =====================
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('id', 'desc')->get();
        $yearId = $request->input('academic_year_id');

        $semesterQuery = Semester::orderBy('id');
        if ($yearId) {
            $semesterQuery->where('academic_year_id', $yearId);
        }
        $semesters = $semesterQuery->get();
        $groups = Group::orderBy('name')->get();

        $groupId = $request->input('group_id');
        $semesterId = $request->input('semester_id');

        $vedomosts = collect();

        if ($groupId && $semesterId) {
            $assignments = SubjectAssignment::with([
                'subject:id,name',
                'teacher:id,first_name,last_name,middle_name',
                'group:id,name,academic_year_id',
            ])
                ->where('group_id', $groupId)
                ->where('semester_id', $semesterId)
                ->get();

            $ids = $assignments->map(function ($a) use ($yearId) {
                return Vedomost::firstOrCreate(
                    ['subject_assignment_id' => $a->id, 'semester_id' => $a->semester_id],
                    [
                        'group_id' => $a->group_id,
                        'subject_id' => $a->subject_id,
                        'teacher_id' => $a->teacher_id,
                        'academic_year_id' => $yearId ?: $a->group?->academic_year_id,
                        'status' => 'draft',
                    ]
                )->id;
            });

            $vedomosts = Vedomost::with([
                'subject:id,name',
                'group:id,name',
                'teacher:id,first_name,last_name',
                'semester:id,number',
                'academicYear:id,name',
            ])
                ->whereIn('id', $ids)
                ->get()
                ->sortBy(fn ($v) => mb_strtolower($v->subject->name ?? ''))
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
            if (! empty($student->user->full_name)) {
                return $student->user->full_name;
            }

            // Агар accessor кор накунад, дастӣ месозем
            $fio = trim(
                ($student->user->last_name ?? '').' '.
                    ($student->user->first_name ?? '').' '.
                    ($student->user->middle_name ?? '')
            );

            if (! empty($fio)) {
                return $fio;
            }
        }

        // 2. Агар user набошад ё ном надошта бошад
        return 'Донишҷӯ #'.($student->student_id_number ?? $student->id);
    }

    // ===================== МАЪЛУМОТИ ВЕДОМОСТ =====================
    protected function buildVedomostData(Vedomost $vedomost): array
    {
        $grades = SemesterGrade::where('subject_assignment_id', $vedomost->subject_assignment_id)
            ->where('semester_id', $vedomost->semester_id)
            ->get()->keyBy('student_id');

        $students = Student::with('user:id,first_name,last_name,middle_name')
            ->where('group_id', $vedomost->group_id)
            ->where('status', 'active')
            ->get()
            ->sortBy(fn ($s) => mb_strtolower($this->studentName($s)))
            ->values();

        $retakeExam = RetakeExam::where('subject_id', $vedomost->subject_id)
            ->where('semester_id', $vedomost->semester_id)
            ->first();

        $retakeStudents = collect();
        if ($retakeExam) {
            $retakeStudents = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
                ->get()
                ->keyBy('student_id');
        }

        $exam = Exam::where('subject_assignment_id', $vedomost->subject_assignment_id)
            ->where('semester_id', $vedomost->semester_id)
            ->where('exam_type', 'main')
            ->latest('starts_at')
            ->first();
        $examAttempts = $exam
            ? ExamAttempt::where('exam_id', $exam->id)
                ->whereIn('status', ['submitted', 'auto_submitted', 'graded'])
                ->get()
                ->sortByDesc('submitted_at')
                ->unique('student_id')
                ->keyBy('student_id')
            : collect();

        $rows = $students->map(function ($s, $i) use ($grades, $retakeStudents, $examAttempts) {
            $studentId = $s->id;
            $g = $grades->get($studentId);

            $r1 = (float) ($g?->rating1_score ?? 0);
            $r2 = (float) ($g?->rating2_score ?? 0);
            $exam = (float) ($g?->exam_score ?? 0);

            $retakeScore = null;
            $retakeStudent = $retakeStudents->get($studentId);
            if ($retakeStudent && $retakeStudent->score !== null) {
                $retakeScore = (float) $retakeStudent->score;
            }

            $ij = $retakeScore !== null ? $retakeScore : $exam;
            $attempt = $examAttempts->get($studentId);
            $displayExamScore = $attempt?->total_score !== null
                ? (float) $attempt->total_score
                : $ij;
            $bjf = round((($r1 + $r2) / 4) + ($displayExamScore * 0.5), 2);

            $gradeEnum = GradeScale::fromPercentage($bjf);
            $eh = $gradeEnum->value;
            $ea = $gradeEnum->gradePoint();
            $eaa = $gradeEnum->traditionalFivePoint();
            $bal = round($eaa * $ea, 2);

            return [
                'n' => $i + 1,
                'code' => $s->student_id_number ?? $s->id,
                'fio' => $this->studentName($s),
                'r1' => number_format($r1, 2),
                'r2' => number_format($r2, 2),
                'ij' => number_format($displayExamScore, 2),
                'bjf' => number_format($bjf, 2),
                'eh' => $eh,
                'ea' => number_format($ea, 2),
                'eaa' => $eaa,
                'bal' => number_format($bal, 2),
            ];
        });
        // Санаи имтиҳон аз журнал (semester_grades.exam_date)
        $examDate = $grades->first(fn ($g) => $g->exam_date)?->exam_date;

        return [
            'v' => $vedomost,
            'rows' => $rows,
            'examDate' => $examDate,
            'institutionName' => Setting::get('institution_name', 'Муассисаи ғайридавлатии коллеҷи тиббии "Даво" Маркази тестӣ'),
            'deputyDirector' => Setting::get('deputy_director_name', 'Гулов М.'),
            'centerHead' => Setting::get('testing_center_head_name', 'Хоҷаев М.М.'),
        ];
    }

    // ===================== PDF-И ЯК ВЕДОМОСТ =====================
    public function downloadPdf(Vedomost $vedomost)
    {
        $data = $this->buildVedomostData($vedomost);
        $pdf = Pdf::loadView('admin.vedomosts.pdf', $data);
        $pdf->setPaper('a4');

        $name = 'vedomost_'.($vedomost->group->name ?? 'group').'_'.($vedomost->subject->name ?? 'fan').'.pdf';

        return $pdf->download($name);
    }

    // ===================== ДИДАНИ ВЕДОМОСТ ДАР БРАУЗЕР =====================
    public function preview(Vedomost $vedomost)
    {
        $data = $this->buildVedomostData($vedomost);
        $pdf = Pdf::loadView('admin.vedomosts.pdf', $data);
        $pdf->setPaper('a4');

        return $pdf->stream('vedomost_'.$vedomost->id.'.pdf');
    }

    // ===================== ZIP-И ҲАМА ВЕДОМОСТҲО =====================
    public function downloadZip(Request $request)
    {
        $vedomosts = Vedomost::with([
            'subject:id,name',
            'group:id,name',
            'teacher:id,first_name,last_name',
            'semester:id,number',
            'academicYear:id,name',
        ])
            ->where('group_id', $request->input('group_id'))
            ->where('semester_id', $request->input('semester_id'))
            ->get()
            ->sortBy(fn ($v) => mb_strtolower($v->subject->name ?? ''))
            ->values();

        if ($vedomosts->isEmpty()) {
            return back()->with('error', 'Ведомост ёфт нашуд!');
        }

        $zipPath = storage_path('app/vedomosts_'.time().'.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($vedomosts as $i => $v) {
            $data = $this->buildVedomostData($v);
            $pdf = Pdf::loadView('admin.vedomosts.pdf', $data);
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

    // ===================== САҲИФАИ ВЕДОМОСТИ ТАКРОРӢ =====================
    public function retakeIndex(Request $request)
    {
        $academicYears = AcademicYear::orderBy('id', 'desc')->get();
        $semesters = Semester::orderBy('id')->get();
        $groups = Group::orderBy('name')->get();

        $academicYearId = $request->input('academic_year_id');
        $semesterId = $request->input('semester_id');
        $groupId = $request->input('group_id');

        $query = RetakeVedomost::with([
            'retakeExam.subject:id,name',
            'retakeExam.semester.academicYear:id,name',
            'group:id,name',
            'academicYear:id,name',
            'semester:id,number',
        ]);

        $yearFilter = function ($query, $yearId) {
            $query->where(function ($query) use ($yearId) {
                $query->where('academic_year_id', $yearId)
                    ->orWhereHas('retakeExam.semester', fn ($semester) => $semester->where('academic_year_id', $yearId));
            });
        };

        if ($academicYearId) {
            $yearFilter($query, $academicYearId);
        } else {
            $currentYear = AcademicYear::current();
            if ($currentYear) {
                $yearFilter($query, $currentYear->id);
            }
        }

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        $retakeVedomosts = $query->orderByDesc('exam_date')->get();

        $retakeExamIds = $retakeVedomosts->pluck('retake_exam_id')->unique();
        $studentCounts = \DB::table('retake_exam_students')
            ->select('retake_exam_id', \DB::raw('COUNT(*) as count'))
            ->whereIn('retake_exam_id', $retakeExamIds)
            ->groupBy('retake_exam_id')
            ->pluck('count', 'retake_exam_id');

        return view('admin.vedomosts.retake-index', compact('retakeVedomosts', 'academicYears', 'semesters', 'groups', 'academicYearId', 'semesterId', 'groupId', 'studentCounts'));
    }

    protected function buildRetakeVedomostData(RetakeVedomost $retakeVedomost): array
    {
        $retakeVedomost->load([
            'retakeExam.subject:id,name',
            'retakeExam.semester:id,number',
            'group:id,name',
        ]);

        $retakeExam = $retakeVedomost->retakeExam;

        $retakeStudents = RetakeExamStudent::where('retake_exam_id', $retakeExam->id)
            ->whereHas('student', fn ($q) => $q->where('group_id', $retakeVedomost->group_id))
            ->with('student.user:id,first_name,last_name,middle_name')
            ->get()
            ->keyBy('student_id');

        $studentIds = $retakeStudents->keys()->toArray();

        $grades = SemesterGrade::whereIn('student_id', $studentIds)
            ->where('semester_id', $retakeExam->semester_id)
            ->get()
            ->keyBy('student_id');

        $attempts = RetakeExamAttempt::where('retake_exam_id', $retakeExam->id)
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('attempt_number')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($items) => $items->first());

        $students = Student::with(['user:id,first_name,last_name,middle_name', 'group:id,name'])
            ->whereIn('id', $studentIds)
            ->where('status', 'active')
            ->get()
            ->sortBy(fn ($s) => mb_strtolower($this->studentName($s)))
            ->values();

        $group = $retakeVedomost->group;

        $rows = $students->map(function ($s, $i) use ($grades, $attempts) {
            $studentId = $s->id;
            $g = $grades->get($studentId);

            $r1 = (float) ($g?->rating1_score ?? 0);
            $r2 = (float) ($g?->rating2_score ?? 0);
            $attempt = $attempts->get($studentId);
            $takr = $attempt && $attempt->total_score !== null ? (float) $attempt->total_score : 0;

            $bjf = round((($r1 + $r2) / 4) + ($takr * 0.5), 2);

            $gradeEnum = GradeScale::fromPercentage($bjf);
            $eh = $gradeEnum->value;
            $ea = $gradeEnum->gradePoint();
            $eaa = $gradeEnum->traditionalFivePoint();
            $bal = round($eaa * $ea, 2);

            return [
                'n' => $i + 1,
                'code' => $s->student_id_number ?? $s->id,
                'fio' => $this->studentName($s),
                'group_name' => $s->group?->name ?? '-',
                'r1' => number_format($r1, 2),
                'r2' => number_format($r2, 2),
                'takr' => number_format($takr, 2),
                'bjf' => number_format($bjf, 2),
                'eh' => $eh,
                'ea' => number_format($ea, 2),
                'eaa' => $eaa,
                'bal' => number_format($bal, 2),
            ];
        });

        $groupedRows = $rows->groupBy('group_name');

        return [
            'retakeExam' => $retakeExam,
            'retakeVedomost' => $retakeVedomost,
            'rows' => $rows,
            'groupedRows' => $groupedRows,
            'students' => $students,
            'group' => $group,
            'institutionName' => Setting::get('institution_name', 'Муассисаи ғайридавлатии коллеҷи тиббии "Даво" Маркази тестӣ'),
            'deputyDirector' => Setting::get('deputy_director_name', 'Гулов М.'),
            'centerHead' => Setting::get('testing_center_head_name', 'Хоҷаев М.М.'),
        ];
    }

    public function retakeGroupVedomost(RetakeVedomost $retakeVedomost)
    {
        $data = $this->buildRetakeVedomostData($retakeVedomost);
        $pdf = Pdf::loadView('admin.vedomosts.retake-pdf', $data);
        $pdf->setPaper('a4');

        return $pdf->stream('retake_vedomost_'.$retakeVedomost->id.'.pdf');
    }

    public function retakeGroupVedomostPdf(RetakeVedomost $retakeVedomost)
    {
        $data = $this->buildRetakeVedomostData($retakeVedomost);
        $pdf = Pdf::loadView('admin.vedomosts.retake-pdf', $data);
        $pdf->setPaper('a4');

        $name = 'retake_vedomost_'.($retakeVedomost->retakeExam->subject->name ?? 'fan').'_'.($retakeVedomost->group->name ?? 'group').'.pdf';

        return $pdf->download($name);
    }
}
