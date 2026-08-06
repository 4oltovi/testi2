<?php

namespace App\Services;

use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\Transcript;
use App\Models\TranscriptLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Хидмати сохтани Transcript (Ведомости баҳоҳо)
 */
class TranscriptGenerator
{
    private GpaCalculator $gpaCalculator;

    public function __construct(GpaCalculator $gpaCalculator)
    {
        $this->gpaCalculator = $gpaCalculator;
    }

    /**
     * Сохтани transcript барои донишҷӯ
     */
    public function generate(Student $student, string $type = 'unofficial'): Transcript
    {
        return DB::transaction(function () use ($student, $type) {
            // Ҳамаи баҳоҳои тасдиқшуда
            $grades = SemesterGrade::where('student_id', $student->id)
                ->where('is_finalized', true)
                ->with(['curriculum.subject', 'semester'])
                ->orderBy('semester_id')
                ->get();

            // Ҳисоби GPA ниҳоӣ
            $totalCreditsEarned = $grades->where('status', 'passed')->sum('credits_earned');
            $totalCreditsRequired = $student->specialty?->total_credits ?? 0;
            $totalSubjectsPassed = $grades->where('status', 'passed')->count();
            $finalGpa = $student->cumulative_gpa;
            $honors = $this->gpaCalculator->determineHonors($finalGpa);

            // Сохтани transcript
            $transcript = Transcript::create([
                'student_id' => $student->id,
                'transcript_number' => $this->generateTranscriptNumber(),
                'issue_date' => now(),
                'type' => $type,
                'final_gpa' => $finalGpa,
                'total_credits_earned' => $totalCreditsEarned,
                'total_credits_required' => $totalCreditsRequired,
                'total_subjects_passed' => $totalSubjectsPassed,
                'total_subjects' => $grades->count(),
                'honors' => $honors,
                'issued_by' => Auth::id() ?? 1,
            ]);

            // Сохтани сатрҳои transcript
            $sortOrder = 0;
            foreach ($grades as $grade) {
                $sortOrder++;
                TranscriptLine::create([
                    'transcript_id' => $transcript->id,
                    'semester_id' => $grade->semester_id,
                    'subject_id' => $grade->curriculum?->subject_id,
                    'semester_grade_id' => $grade->id,
                    'subject_name' => $grade->curriculum?->subject?->name ?? 'Номаълум',
                    'subject_code' => $grade->curriculum?->subject?->code ?? '',
                    'credits' => $grade->curriculum?->credits ?? 0,
                    'total_score' => $grade->total_score,
                    'letter_grade' => $grade->letter_grade ?? 'F',
                    'grade_point' => $grade->grade_point ?? 0,
                    'traditional_grade' => $grade->traditional_grade,
                    'status' => $grade->status === 'passed' ? 'passed' : 'failed',
                    'sort_order' => $sortOrder,
                ]);
            }

            return $transcript;
        });
    }

    /**
     * Гирифтани маълумот барои PDF
     */
    public function getTranscriptData(Transcript $transcript): array
    {
        $student = $transcript->student()->with(['user', 'group', 'specialty.department.faculty', 'course'])->first();
        $lines = $transcript->lines()->with('semester.academicYear')->orderBy('sort_order')->get();

        // Гурӯҳбандӣ аз рӯйи семестр
        $bySemester = $lines->groupBy('semester_id');

        return [
            'transcript' => $transcript,
            'student' => [
                'full_name' => $student->user->full_name,
                'student_id' => $student->student_id_number,
                'record_book' => $student->record_book_number,
                'faculty' => $student->specialty?->department?->faculty?->name,
                'department' => $student->specialty?->department?->name,
                'specialty' => $student->specialty?->name,
                'specialty_code' => $student->specialty?->code,
                'education_form' => $student->education_form === 'budget' ? 'Буҷетӣ' : 'Шартномавӣ',
                'study_form' => match ($student->study_form) {
                    'full_time' => 'Рӯзона',
                    'part_time' => 'Ғоибона',
                    'evening' => 'Шабона',
                },
                'enrollment_date' => $student->enrollment_date?->format('d.m.Y'),
                'enrollment_order' => $student->enrollment_order,
            ],
            'semesters' => $bySemester->map(function ($semesterLines) {
                $semester = $semesterLines->first()->semester;
                return [
                    'semester_name' => $semester?->name,
                    'academic_year' => $semester?->academicYear?->name,
                    'subjects' => $semesterLines->map(fn($line) => [
                        'name' => $line->subject_name,
                        'code' => $line->subject_code,
                        'credits' => $line->credits,
                        'score' => $line->total_score,
                        'letter_grade' => $line->letter_grade,
                        'grade_point' => $line->grade_point,
                        'traditional' => $line->traditional_grade,
                        'status' => $line->status,
                    ]),
                ];
            }),
            'summary' => [
                'final_gpa' => $transcript->final_gpa,
                'total_credits_earned' => $transcript->total_credits_earned,
                'total_credits_required' => $transcript->total_credits_required,
                'total_subjects_passed' => $transcript->total_subjects_passed,
                'total_subjects' => $transcript->total_subjects,
                'honors' => $transcript->honors,
                'honors_label' => GpaCalculator::honorsLabel($transcript->honors),
            ],
        ];
    }

    /**
     * Генератсияи рақами transcript
     */
    private function generateTranscriptNumber(): string
    {
        $year = date('Y');
        $lastNumber = Transcript::where('transcript_number', 'like', "TR-{$year}-%")
            ->count();
        return sprintf('TR-%s-%04d', $year, $lastNumber + 1);
    }
}
