<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SemesterGrade;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Transcript;
use App\Services\GpaCalculator;
use App\Services\TranscriptGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TranscriptController extends Controller
{
    private TranscriptGenerator $transcriptGenerator;
    private GpaCalculator $gpaCalculator;

    public function __construct(TranscriptGenerator $transcriptGenerator, GpaCalculator $gpaCalculator)
    {
        $this->transcriptGenerator = $transcriptGenerator;
        $this->gpaCalculator = $gpaCalculator;
    }

    public function index(Request $request): View
    {
        $query = Student::with(['user', 'group', 'specialty', 'course'])
            ->active()
            ->orderBy('cumulative_gpa', 'desc');

        if ($search = $request->get('search')) {
            $query->whereHas(
                'user',
                fn($q) =>
                $q->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
            )->orWhere('student_id_number', 'like', "%{$search}%");
        }

        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }

        $students = $query->paginate(25)->withQueryString();
        $groups = \App\Models\Group::active()->orderBy('name')->get();

        return view('admin.transcript.index', compact('students', 'groups'));
    }

    public function show(Student $student): View
    {
        $student->load([
            'user',
            'group',
            'specialty.department.faculty',
            'course',
            'semesterGrades' => fn($q) => $q->where('is_finalized', true)->with(['curriculum.subject', 'semester']),
            'semesterGpas.semester',
        ]);

        $gradesBySemester = $student->semesterGrades->groupBy('semester_id');
        $totalCreditsEarned = $student->semesterGrades->where('status', 'passed')->sum('credits_earned');
        $totalCreditsRequired = $student->specialty?->total_credits ?? 0;
        $honors = $this->gpaCalculator->determineHonors($student->cumulative_gpa);
        $honorsLabel = GpaCalculator::honorsLabel($honors);

        return view('admin.transcript.show', compact(
            'student',
            'gradesBySemester',
            'totalCreditsEarned',
            'totalCreditsRequired',
            'honors',
            'honorsLabel'
        ));
    }

    public function generate(Student $student): RedirectResponse
    {
        try {
            $transcript = $this->transcriptGenerator->generate($student, 'official');
            return back()->with('success', "Transcript сохта шуд: {$transcript->transcript_number}");
        } catch (\Exception $e) {
            return back()->with('error', "Хатогӣ: {$e->getMessage()}");
        }
    }

    // ===================== PDF (НАМУНАИ НАВ) =====================
    public function exportPdf(Transcript $transcript)
    {
        $student = $transcript->student ?? Student::findOrFail($transcript->student_id);
        return $this->renderPdf($student, $transcript->transcript_number ?? null);
    }

    public function printStudent(Student $student)
    {
        return $this->renderPdf($student, null);
    }

    protected function renderPdf(Student $student, ?string $number)
    {
        $data = $this->buildTranscriptData($student, $number);
        $pdf = Pdf::loadView('admin.transcript.pdf', $data);
        $pdf->setPaper('a4');

        return $pdf->stream('transcript_' . ($student->student_id_number ?? $student->id) . '.pdf');
    }

    protected function buildTranscriptData(Student $student, ?string $number): array
    {
        $student->load(['user', 'group', 'specialty.department.faculty', 'course']);

        $grades = SemesterGrade::with(['curriculum.subject', 'curriculum.course', 'subjectAssignment.group', 'semester'])
            ->where('student_id', $student->id)
            ->get()
            ->sortBy(function ($g) {
                return [
                    (int) ($g->semester?->number ?? $g->semester_id),
                    mb_strtolower($g->curriculum?->subject?->name ?? ''),
                ];
            });

        $rows = $grades->map(function ($g) use ($student) {
            $cur  = $g->curriculum;
            $subj = $cur?->subject;
            $semNumber = (int) ($g->semester?->number ?? $g->semester_id);
            $isPE = mb_str_contains(mb_strtolower($subj?->name ?? ''), 'тарбияи ҷисмонӣ');
            $hasGrade = $g->letter_grade !== null;

            $point = (float) ($g->grade_point ?? 0);
            $credEarned = (int) ($g->credits_earned ?? 0);

            return [
                'course'  => $cur?->course?->number ?? $cur?->course?->name ?? '',
                'sem'     => (($semNumber - 1) % 2) + 1,
                'group'   => $g->subjectAssignment?->group?->name ?? $student->group?->name ?? '',
                'subject' => $subj?->name ?? '-',
                'r1'      => $g->rating1_score !== null ? number_format((float) $g->rating1_score, 2) : '',
                'r2'      => $g->rating2_score !== null ? number_format((float) $g->rating2_score, 2) : '',
                'exam'    => $g->exam_score !== null ? number_format((float) $g->exam_score, 2) : '',
                'total'   => ($isPE || !$hasGrade) ? 'Комёб' : ($g->total_score ?? ''),
                'letter'  => $hasGrade ? $g->letter_grade : '',
                'trad'    => $hasGrade ? ($g->traditional_grade ?? '') : '',
                'point'   => $hasGrade ? number_format($point, 2) : '',
                'credits' => (int) ($cur?->credits ?? 0),
                'earned'  => $credEarned,
                'ball'    => ($hasGrade && $point * $credEarned > 0) ? number_format($point * $credEarned, 2) : '',
                'component' => ($cur?->is_elective ? 'Интихобӣ' : 'Ҳатмӣ'),
            ];
        })->values();

        // Хулоса по семестрҳо
        $summary = $grades->groupBy(fn($g) => (int) ($g->semester?->number ?? $g->semester_id))
            ->map(function ($group, $semId) {
                $ball = 0;
                $credits = 0;
                $earned = 0;
                foreach ($group as $g) {
                    $credits += (int) ($g->curriculum?->credits ?? 0);
                    $earned  += (int) ($g->credits_earned ?? 0);
                    $ball    += (float) $g->grade_point * (int) $g->credits_earned;
                }
                return [
                    'sem'     => $semId,
                    'ball'    => number_format($ball, 2),
                    'credits' => $credits,
                    'earned'  => $earned,
                    'gpa'     => $earned > 0 ? number_format($ball / $earned, 2) : '0.00',
                ];
            })
            ->sortBy('sem')->values();

        $totalEarned = $grades->sum(fn($g) => (int) $g->credits_earned);
        $totalMandatory = $grades->sum(fn($g) => ($g->curriculum && !$g->curriculum->is_elective) ? (int) $g->credits_earned : 0);

        $studyForm = match ($student->study_form ?? 'full_time') {
            'full_time' => 'рӯзона',
            'part_time' => 'ғоибона',
            'evening'   => 'шомина',
            default     => $student->study_form ?? 'рӯзона',
        };

        return [
            'student'      => $student,
            'rows'         => $rows,
            'summary'      => $summary,
            'totalEarned'  => $totalEarned,
            'totalMandatory' => $totalMandatory,
            'studyForm'    => $studyForm,
            'bahshGroup'   => ($student->course?->number ?? $student->course?->name ?? '') . '-' . ($student->group?->code ?? $student->group?->name ?? ''),
            'specialtyCode' => $student->specialty?->code ?? '',
            'facultyName'  => optional(optional(optional($student->specialty)->department)->faculty)->name ?? optional($student->specialty)->name ?? '-',
            'transcriptNumber' => $number,
            'date'         => now(),
            'institutionName'  => Setting::get('institution_name', 'Муассисаи ғайридавлатии коллеҷи тиббии "Даво" Маркази тестӣ'),
            'deputyDirector'   => Setting::get('deputy_director_name', 'Гулов М.'),
            'centerHead'       => Setting::get('testing_center_head_name', 'Хоҷаев М.М.'),
        ];
    }
}
