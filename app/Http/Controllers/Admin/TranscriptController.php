<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Transcript;
use App\Services\GpaCalculator;
use App\Services\TranscriptGenerator;
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
            $query->whereHas('user', fn($q) =>
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
            'user', 'group', 'specialty.department.faculty', 'course',
            'semesterGrades' => fn($q) => $q->where('is_finalized', true)->with(['curriculum.subject', 'semester']),
            'semesterGpas.semester',
        ]);

        // Гурӯҳбандӣ аз рӯйи семестр
        $gradesBySemester = $student->semesterGrades->groupBy('semester_id');

        // Кредитҳо
        $totalCreditsEarned = $student->semesterGrades->where('status', 'passed')->sum('credits_earned');
        $totalCreditsRequired = $student->specialty?->total_credits ?? 0;

        // Honors
        $honors = $this->gpaCalculator->determineHonors($student->cumulative_gpa);
        $honorsLabel = GpaCalculator::honorsLabel($honors);

        return view('admin.transcript.show', compact(
            'student', 'gradesBySemester', 'totalCreditsEarned',
            'totalCreditsRequired', 'honors', 'honorsLabel'
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

    public function exportPdf(Transcript $transcript): View
    {
        $data = $this->transcriptGenerator->getTranscriptData($transcript);
        return view('admin.transcript.pdf', $data);
    }
}
