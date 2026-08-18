<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CurrentGrade;
use App\Models\GradeChangeLog;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\SubjectAssignment;
use App\Services\DebtDetector;
use App\Services\GradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JournalController extends Controller
{
    private GradeCalculator $gradeCalculator;
    private DebtDetector $debtDetector;

    public function __construct(GradeCalculator $gradeCalculator, DebtDetector $debtDetector)
    {
        $this->gradeCalculator = $gradeCalculator;
        $this->debtDetector = $debtDetector;
    }

    /**
     * НАВ: Рӯйхати таъинотҳои омӯзгор
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);

        $query = SubjectAssignment::with(['subject', 'group', 'semester'])
            ->where('teacher_id', $user->id)
            ->where('is_active', true);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        $assignments = $query->orderBy('group_id')->paginate(20)->withQueryString();
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        return view('teacher.journal.index', compact(
            'assignments',
            'semesters',
            'currentSemester',
            'semesterId'
        ));
    }

    /**
     * НАВ: Давомот — намоиш
     */
    public function attendance(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');

        $date = $request->get('date', now()->format('Y-m-d'));
        $lessonNumber = $request->get('lesson_number', 1);

        $existingAttendance = Attendance::where('subject_assignment_id', $subjectAssignment->id)
            ->where('lesson_date', $date)
            ->where('lesson_number', $lessonNumber)
            ->pluck('status', 'student_id');

        $attendanceStats = Attendance::where('subject_assignment_id', $subjectAssignment->id)
            ->selectRaw('student_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = "present" OR status = "late" THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        return view('teacher.journal.attendance', compact(
            'subjectAssignment',
            'students',
            'date',
            'lessonNumber',
            'existingAttendance',
            'attendanceStats'
        ));
    }

    /**
     * НАВ: Сабти давомот
     */
    public function storeAttendance(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $request->validate([
            'date' => 'required|date',
            'lesson_number' => 'required|integer|min:1|max:8',
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent,excused,late,sick',
        ]);

        $date = $request->input('date');
        $lessonNumber = $request->input('lesson_number');

        DB::transaction(function () use ($subjectAssignment, $request, $date, $lessonNumber) {
            foreach ($request->input('attendance') as $studentId => $status) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_assignment_id' => $subjectAssignment->id,
                        'lesson_date' => $date,
                        'lesson_number' => $lessonNumber,
                    ],
                    ['status' => $status, 'marked_by' => auth()->id()]
                );
            }
        });

        return back()->with('success', "Давомот барои санаи {$date} сабт шуд.");
    }

    /**
     * НАВ: Баҳоҳои ҷорӣ — намоиш
     */
    public function grades(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');
        $semester = $subjectAssignment->semester;

        $grades = CurrentGrade::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->orderBy('grade_date')
            ->get()
            ->groupBy('student_id');

        $weekNumber = $request->get('week', null);

        return view('teacher.journal.grades', compact(
            'subjectAssignment',
            'students',
            'semester',
            'grades',
            'weekNumber'
        ));
    }

    /**
     * НАВ: Сабти баҳои ҷорӣ
     */
    public function storeGrades(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $request->validate([
            'grade_date' => 'required|date',
            'week_number' => 'required|integer|min:1|max:18',
            'grade_type' => 'required|in:homework,classwork,quiz,lab_work,presentation,project,essay,other',
            'max_score' => 'required|numeric|min:1|max:100',
            'grades' => 'required|array',
            'grades.*' => 'nullable|numeric|min:0',
        ]);

        $semester = $subjectAssignment->semester;

        DB::transaction(function () use ($subjectAssignment, $request, $semester) {
            foreach ($request->input('grades') as $studentId => $score) {
                if ($score === null || $score === '') continue;

                CurrentGrade::create([
                    'student_id' => $studentId,
                    'subject_assignment_id' => $subjectAssignment->id,
                    'semester_id' => $semester->id,
                    'grade_date' => $request->input('grade_date'),
                    'week_number' => $request->input('week_number'),
                    'grade_type' => $request->input('grade_type'),
                    'score' => $score,
                    'max_score' => $request->input('max_score'),
                    'graded_by' => auth()->id(),
                ]);
            }
        });

        return back()->with('success', 'Баҳоҳо сабт шуданд.');
    }

    /**
     * Баҳоҳои семестрӣ
     */
    public function semesterGrades(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents
            ->sortBy(function ($student) {
                return $student->user?->last_name ?? '' . $student->user?->first_name ?? '';
            })
            ->values();

        $semester = $subjectAssignment->semester;
        $subject = $subjectAssignment->subject;

        $semesterGrades = SemesterGrade::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->get()
            ->keyBy('student_id');

        $calculatedGrades = [];
        foreach ($students as $student) {
            $rating1 = $this->gradeCalculator->calculateRating1($student->id, $subjectAssignment->id, $semester->id);
            $rating2 = $this->gradeCalculator->calculateRating2($student->id, $subjectAssignment->id, $semester->id);
            $exam = $this->gradeCalculator->calculateExamPercentage($student->id, $subjectAssignment->id, $semester->id);

            $totalScore = null;
            $letterGrade = null;
            $gradePoint = null;
            $status = null;

            $hasRating = $rating1 > 0 || $rating2 > 0;
            $hasExam = $exam > 0;

            if ($hasRating || $hasExam) {
                $divisor = (float) \App\Models\Setting::get('rating_part_divisor', 4);
                $examWeight = (float) \App\Models\Setting::get('exam_weight', 0.5);

                $r1 = (float) $rating1;
                $r2 = (float) $rating2;

                $totalScore = round(($r1 + $r2) / $divisor + ($exam * $examWeight), 2);

                $gradeEnum = \App\Enums\GradeScale::fromPercentage($totalScore);
                $letterGrade = $gradeEnum->value;
                $gradePoint = $gradeEnum->gradePoint();
                $status = $gradeEnum->isPassing() ? 'passed' : ($gradeEnum->canRetake() ? 'retake' : 'failed');
            }

            $calculatedGrades[$student->id] = [
                'rating1' => $rating1,
                'rating2' => $rating2,
                'exam' => $exam,
                'total_score' => $totalScore,
                'letter_grade' => $letterGrade,
                'grade_point' => $gradePoint,
                'status' => $status,
            ];
        }

        return view('teacher.journal.semester-grades', compact(
            'subjectAssignment',
            'students',
            'semester',
            'subject',
            'semesterGrades',
            'calculatedGrades'
        ));
    }

    /**
     * Сабти рейтинг (R1/R2/КМ)
     */
    public function setRating(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $request->validate([
            'ratings' => 'required|array',
            'ratings.*.student_id' => 'required|exists:students,id',
            'ratings.*.rating1_score' => 'nullable|numeric|min:0|max:100',
            'ratings.*.rating2_score' => 'nullable|numeric|min:0|max:100',
            'ratings.*.independent_work_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $semester = $subjectAssignment->semester;

        DB::transaction(function () use ($subjectAssignment, $request, $semester) {
            foreach ($request->input('ratings') as $rating) {
                $studentId = $rating['student_id'];

                $semesterGrade = SemesterGrade::firstOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_assignment_id' => $subjectAssignment->id,
                        'semester_id' => $semester->id,
                    ],
                    ['status' => 'in_progress']
                );

                if (!$semesterGrade->is_finalized) {
                    $updates = [];

                    if (isset($rating['rating1_score']) && $rating['rating1_score'] !== '') {
                        $updates['rating1_score'] = $rating['rating1_score'];
                        $updates['rating1_date'] = now();
                    }

                    if (isset($rating['rating2_score']) && $rating['rating2_score'] !== '') {
                        $updates['rating2_score'] = $rating['rating2_score'];
                        $updates['rating2_date'] = now();
                    }

                    if (isset($rating['independent_work_score']) && $rating['independent_work_score'] !== '') {
                        $updates['independent_work_score'] = $rating['independent_work_score'];
                    }

                    if (!empty($updates)) {
                        $semesterGrade->update($updates);
                        $this->gradeCalculator->processAndSaveFinalGrade($semesterGrade);
                    }
                }
            }
        });

        return back()->with('success', 'Рейтингҳо сабт шуданд.');
    }

    /**
     * Сабти баҳои имтиҳон
     */
    public function setExamScore(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $request->validate([
            'exam_scores' => 'required|array',
            'exam_scores.*.student_id' => 'required|exists:students,id',
            'exam_scores.*.exam_score' => 'nullable|numeric|min:0|max:100',
            'exam_type' => 'required|in:main,retake,retake2',
        ]);

        $semester = $subjectAssignment->semester;
        $examType = $request->input('exam_type');

        DB::transaction(function () use ($subjectAssignment, $request, $semester, $examType) {
            foreach ($request->input('exam_scores') as $data) {
                $studentId = $data['student_id'];
                $score = $data['exam_score'];

                if ($score === null || $score === '') continue;

                $semesterGrade = SemesterGrade::firstOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_assignment_id' => $subjectAssignment->id,
                        'semester_id' => $semester->id,
                    ],
                    ['status' => 'in_progress']
                );

                if ($semesterGrade->is_finalized) continue;

                $field = match ($examType) {
                    'main' => 'exam_score',
                    'retake' => 'retake_score',
                    'retake2' => 'retake2_score',
                };
                $dateField = match ($examType) {
                    'main' => 'exam_date',
                    'retake' => 'retake_date',
                    'retake2' => 'retake2_date',
                };

                $this->logChange($semesterGrade, $field, $semesterGrade->$field, $score);

                $semesterGrade->update([
                    $field => $score,
                    $dateField => now(),
                    'exam_teacher_id' => auth()->id(),
                ]);

                $this->gradeCalculator->processAndSaveFinalGrade($semesterGrade);
            }
        });

        return back()->with('success', 'Баҳоҳои имтиҳон сабт шуданд.');
    }

    /**
     * Санҷиши дастрасии омӯзгор
     */
    private function authorizeTeacher(SubjectAssignment $subjectAssignment, Request $request): void
    {
        abort_unless(
            $subjectAssignment->teacher_id === $request->user()->id
                || $request->user()->hasRole(['admin', 'super_admin']),
            403
        );
    }

    /**
     * Сабти тағйирот
     */
    private function logChange($semesterGrade, string $field, $oldValue, $newValue): void
    {
        GradeChangeLog::create([
            'semester_grade_id' => $semesterGrade->id,
            'student_id' => $semesterGrade->student_id,
            'field_changed' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => 'Сабти баҳо аз ҷониби омӯзгор',
            'changed_by' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);
    }
}
