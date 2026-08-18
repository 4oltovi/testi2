<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\AttendanceStatus;
use App\Enums\GradeScale;
use App\Models\Attendance;
use App\Models\CurrentGrade;
use App\Models\GradeChangeLog;
use App\Models\Group;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
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
     * Рӯйхати таъинотҳо (омӯзгор-фан-гурӯҳ)
     */
    public function index(Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);

        $query = SubjectAssignment::with(['subject', 'teacher', 'group', 'semester'])
            ->where('is_active', true);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        if ($groupId = $request->get('group_id')) {
            $query->where('group_id', $groupId);
        }

        if ($teacherId = $request->get('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }

        $assignments = $query->orderBy('group_id')->paginate(30)->withQueryString();
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();

        $semesters = Semester::with('academicYear')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->orderBy('number')
            ->get();
        $groups = Group::active()->orderBy('name')->get();

        return view('admin.journal.index', compact('assignments', 'semesters', 'groups', 'currentSemester', 'semesterId'));
    }

    public function createAssignment(): View
    {
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->where('status', 'active')->orderBy('user_id')->get();
        $groups = Group::where('is_active', true)->orderBy('name')->get();
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();

        $semesters = Semester::with('academicYear')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->orderBy('number')
            ->get();

        return view('admin.journal.assignment-create', compact('subjects', 'teachers', 'groups', 'semesters'));
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'group_ids' => 'required|array|min:1',
            'group_ids.*' => 'required|integer|exists:groups,id',
            'semester_id' => 'required|exists:semesters,id',
            'lesson_type' => 'nullable|in:lecture,practice,lab',
            'hours_per_week' => 'nullable|integer|min:1|max:20',
            'credits' => 'nullable|integer|min:1|max:30',   
        ]);

        $subjectId = $request->subject_id;
        $semesterId = $request->semester_id;

        // Санҷиш: оё фан мавҷуд аст?
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return back()->withErrors(['subject_id' => 'Фан ёфт нашуд.'])->withInput();
        }

        $created = 0;

        foreach ($request->group_ids as $groupId) {
            $assignment = SubjectAssignment::firstOrCreate(
                [
                    'subject_id' => $subjectId,
                    'teacher_id' => $request->teacher_id,
                    'group_id' => $groupId,
                    'semester_id' => $semesterId,
                    'lesson_type' => $request->lesson_type ?? 'practice',
                ],
                [
                    'hours_per_week' => $request->hours_per_week ?? 2,
                    'is_active' => true,
                    'credits' => $request->credits,
                ]
            );

            if ($assignment->wasRecentlyCreated || $assignment->wasChanged()) {
                $created++;
            }
        }

        return redirect()->route('admin.journal.index', ['semester_id' => $semesterId])
            ->with('success', $created . ' таъин барои семестри интихобшуда сохта шуд.');
    }

    /**
     * НАВ: Навсозии кредит аз журнал
     */
    public function updateCredits(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
        $request->validate(['credits' => 'required|integer|min:1|max:30']);

        $subjectAssignment->update(['credits' => $request->integer('credits')]);

        return back()->with('success', "Миқдори кредит навсозӣ шуд: {$request->integer('credits')}");
    }
    /**
     * Давомот — намоиш ва сабт
     */
    public function attendance(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'teacher', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');

        // Санаи интихобшуда
        $date = $request->get('date', now()->format('Y-m-d'));
        $lessonNumber = $request->get('lesson_number', 1);

        // Давоомоти мавҷуда барои ин сана
        $existingAttendance = Attendance::where('subject_assignment_id', $subjectAssignment->id)
            ->where('lesson_date', $date)
            ->where('lesson_number', $lessonNumber)
            ->pluck('status', 'student_id');

        // Омори давомот
        $attendanceStats = Attendance::where('subject_assignment_id', $subjectAssignment->id)
            ->selectRaw('student_id, 
                COUNT(*) as total,
                SUM(CASE WHEN status = "present" OR status = "late" THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        return view('admin.journal.attendance', compact(
            'subjectAssignment',
            'students',
            'date',
            'lessonNumber',
            'existingAttendance',
            'attendanceStats'
        ));
    }

    /**
     * Сабти давомот
     */
    public function storeAttendance(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
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
                    [
                        'status' => $status,
                        'marked_by' => auth()->id(),
                    ]
                );
            }
        });

        return back()->with('success', "Давомот барои санаи {$date} бомуваффақият сабт шуд.");
    }

    /**
     * Баҳоҳои ҷорӣ — намоиш ва сабт
     */
    public function grades(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'teacher', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');
        $semester = $subjectAssignment->semester;

        // Баҳоҳои мавҷуда
        $grades = CurrentGrade::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->orderBy('grade_date')
            ->get()
            ->groupBy('student_id');

        // Ҳафтаи интихобшуда
        $weekNumber = $request->get('week', null);

        return view('admin.journal.grades', compact(
            'subjectAssignment',
            'students',
            'semester',
            'grades',
            'weekNumber'
        ));
    }

    /**
     * Сабти баҳои ҷорӣ
     */
    public function storeGrades(SubjectAssignment $subjectAssignment, Request $request): RedirectResponse
    {
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

        return back()->with('success', 'Баҳоҳо бомуваффақият сабт шуданд.');
    }

    /**
     * Баҳоҳои семестрӣ (R1, R2, КМ, Имтиҳон, Ниҳоӣ)
     */
    public function semesterGrades(SubjectAssignment $subjectAssignment): View
    {
        $subjectAssignment->load(['subject', 'group.activeStudents.user', 'teacher', 'semester']);
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

            if ($exam > 0 || ($rating1 > 0 || $rating2 > 0)) {
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

        return view('admin.journal.semester-grades', compact(
            'subjectAssignment',
            'students',
            'semester',
            'subject',
            'semesterGrades',
            'calculatedGrades'
        ));
    }

    /**
     * Тасдиқи баҳои ниҳоӣ
     */
    public function finalize(SemesterGrade $semesterGrade): RedirectResponse
    {
        if ($semesterGrade->is_finalized) {
            return back()->with('error', 'Ин баҳо аллакай тасдиқ шудааст.');
        }

        DB::transaction(function () use ($semesterGrade) {
            // Ҳисоби баҳои ниҳоӣ
            $this->gradeCalculator->processAndSaveFinalGrade($semesterGrade);

            // Тасдиқ
            $semesterGrade->update([
                'is_finalized' => true,
                'finalized_at' => now(),
                'finalized_by' => auth()->id(),
            ]);

            // Санҷиши қарздорӣ
            $this->debtDetector->checkAndCreateDebt($semesterGrade);

            // Сабти лог
            GradeChangeLog::create([
                'semester_grade_id' => $semesterGrade->id,
                'student_id' => $semesterGrade->student_id,
                'field_changed' => 'finalized',
                'old_value' => 'false',
                'new_value' => 'true',
                'reason' => 'Тасдиқи баҳои ниҳоӣ',
                'changed_by' => auth()->id(),
                'ip_address' => request()->ip(),
            ]);
        });

        $grade = GradeScale::tryFrom($semesterGrade->letter_grade);
        $message = "Баҳо тасдиқ шуд: {$semesterGrade->letter_grade} ({$semesterGrade->total_score}%)";
        if ($grade && !$grade->isPassing()) {
            $message .= ' — ҚАРЗДОР!';
        }

        return back()->with('success', $message);
    }
}
