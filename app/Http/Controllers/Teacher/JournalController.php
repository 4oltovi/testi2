<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Enums\AttendanceStatus;
use App\Enums\GradeScale;
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
     * Фанҳои омӯзгор дар ин семестр
     */
    public function index(Request $request): View
    {
        $teacher = $request->user();
        $currentSemester = Semester::current();

        $assignments = SubjectAssignment::where('teacher_id', $teacher->id)
            ->where('semester_id', $currentSemester?->id)
            ->where('is_active', true)
            ->with(['curriculum.subject', 'group', 'semester'])
            ->orderBy('group_id')
            ->get();

        return view('teacher.journal.index', compact('assignments', 'currentSemester'));
    }

    /**
     * Давомот
     */
    public function attendance(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['curriculum.subject', 'group.activeStudents.user']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');

        $date = $request->get('date', now()->format('Y-m-d'));
        $lessonNumber = $request->get('lesson_number', 1);

        $existingAttendance = Attendance::where('subject_assignment_id', $subjectAssignment->id)
            ->where('lesson_date', $date)
            ->where('lesson_number', $lessonNumber)
            ->pluck('status', 'student_id');

        return view('teacher.journal.attendance', compact(
            'subjectAssignment', 'students', 'date', 'lessonNumber', 'existingAttendance'
        ));
    }

    /**
     * Сабти давомот
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

        DB::transaction(function () use ($subjectAssignment, $request) {
            foreach ($request->input('attendance') as $studentId => $status) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_assignment_id' => $subjectAssignment->id,
                        'lesson_date' => $request->input('date'),
                        'lesson_number' => $request->input('lesson_number'),
                    ],
                    [
                        'status' => $status,
                        'marked_by' => auth()->id(),
                    ]
                );
            }
        });

        return back()->with('success', 'Давомот сабт шуд.');
    }

    /**
     * Баҳоҳои ҷорӣ
     */
    public function grades(SubjectAssignment $subjectAssignment, Request $request): View
    {
        $this->authorizeTeacher($subjectAssignment, $request);

        $subjectAssignment->load(['curriculum.subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');
        $semester = $subjectAssignment->semester;

        $grades = CurrentGrade::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->orderBy('week_number')
            ->orderBy('grade_date')
            ->get()
            ->groupBy('student_id');

        return view('teacher.journal.grades', compact('subjectAssignment', 'students', 'semester', 'grades'));
    }

    /**
     * Сабти баҳо
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

        $subjectAssignment->load(['curriculum.subject', 'group.activeStudents.user', 'semester']);
        $students = $subjectAssignment->group->activeStudents->sortBy('user.last_name');
        $semester = $subjectAssignment->semester;
        $curriculum = $subjectAssignment->curriculum;

        $semesterGrades = SemesterGrade::where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $semester->id)
            ->get()
            ->keyBy('student_id');

        // Автоматикӣ ҳисоби рейтингҳо
        $calculatedRatings = [];
        foreach ($students as $student) {
            $calculatedRatings[$student->id] = [
                'rating1' => $this->gradeCalculator->calculateRating1($student->id, $subjectAssignment->id, $semester->id),
                'rating2' => $this->gradeCalculator->calculateRating2($student->id, $subjectAssignment->id, $semester->id),
            ];
        }

        return view('teacher.journal.semester-grades', compact(
            'subjectAssignment', 'students', 'semester', 'curriculum',
            'semesterGrades', 'calculatedRatings'
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
        $curriculum = $subjectAssignment->curriculum;

        DB::transaction(function () use ($subjectAssignment, $request, $semester, $curriculum) {
            foreach ($request->input('ratings') as $rating) {
                $studentId = $rating['student_id'];

                $semesterGrade = SemesterGrade::firstOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_assignment_id' => $subjectAssignment->id,
                        'semester_id' => $semester->id,
                    ],
                    [
                        'curriculum_id' => $curriculum->id,
                        'status' => 'in_progress',
                    ]
                );

                // Навсозии баҳоҳо (танҳо агар тасдиқ нашуда бошад)
                if (!$semesterGrade->is_finalized) {
                    $updates = [];

                    if (isset($rating['rating1_score']) && $rating['rating1_score'] !== '') {
                        if ($semesterGrade->rating1_score != $rating['rating1_score']) {
                            $this->logChange($semesterGrade, 'rating1_score', $semesterGrade->rating1_score, $rating['rating1_score']);
                        }
                        $updates['rating1_score'] = $rating['rating1_score'];
                        $updates['rating1_date'] = now();
                    }

                    if (isset($rating['rating2_score']) && $rating['rating2_score'] !== '') {
                        if ($semesterGrade->rating2_score != $rating['rating2_score']) {
                            $this->logChange($semesterGrade, 'rating2_score', $semesterGrade->rating2_score, $rating['rating2_score']);
                        }
                        $updates['rating2_score'] = $rating['rating2_score'];
                        $updates['rating2_date'] = now();
                    }

                    if (isset($rating['independent_work_score']) && $rating['independent_work_score'] !== '') {
                        $updates['independent_work_score'] = $rating['independent_work_score'];
                    }

                    if (!empty($updates)) {
                        $semesterGrade->update($updates);
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
        $curriculum = $subjectAssignment->curriculum;
        $examType = $request->input('exam_type');

        DB::transaction(function () use ($subjectAssignment, $request, $semester, $curriculum, $examType) {
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
                    [
                        'curriculum_id' => $curriculum->id,
                        'status' => 'in_progress',
                    ]
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
            }
        });

        $label = match ($examType) {
            'main' => 'Имтиҳони асосӣ',
            'retake' => 'Такрорсупорӣ',
            'retake2' => 'Комиссионӣ',
        };

        return back()->with('success', "Баҳоҳои «{$label}» сабт шуданд.");
    }

    /**
     * Тасдиқи баҳои ниҳоӣ
     */
    public function finalize(SemesterGrade $semesterGrade, Request $request): RedirectResponse
    {
        // Санҷиши иҷозат
        $assignment = $semesterGrade->subjectAssignment;
        if ($assignment && $assignment->teacher_id !== $request->user()->id) {
            abort(403, 'Шумо танҳо баҳоҳои фанҳои худро тасдиқ карда метавонед.');
        }

        if ($semesterGrade->is_finalized) {
            return back()->with('error', 'Ин баҳо аллакай тасдиқ шудааст.');
        }

        DB::transaction(function () use ($semesterGrade) {
            $this->gradeCalculator->processAndSaveFinalGrade($semesterGrade);

            $semesterGrade->update([
                'is_finalized' => true,
                'finalized_at' => now(),
                'finalized_by' => auth()->id(),
            ]);

            // Қарздорӣ
            $this->debtDetector->checkAndCreateDebt($semesterGrade);

            GradeChangeLog::create([
                'semester_grade_id' => $semesterGrade->id,
                'student_id' => $semesterGrade->student_id,
                'field_changed' => 'finalized',
                'old_value' => 'false',
                'new_value' => 'true',
                'reason' => 'Тасдиқ аз тарафи омӯзгор',
                'changed_by' => auth()->id(),
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', "Баҳо тасдиқ шуд: {$semesterGrade->letter_grade} ({$semesterGrade->total_score}%)");
    }

    /**
     * Санҷиши ки ин таъинот аз они ин омӯзгор аст
     */
    private function authorizeTeacher(SubjectAssignment $assignment, Request $request): void
    {
        if ($assignment->teacher_id !== $request->user()->id) {
            abort(403, 'Шумо ба ин фан/гурӯҳ дастрасӣ надоред.');
        }
    }

    /**
     * Сабти тағйирот дар лог
     */
    private function logChange(SemesterGrade $grade, string $field, $oldValue, $newValue): void
    {
        if ($oldValue == $newValue) return;

        GradeChangeLog::create([
            'semester_grade_id' => $grade->id,
            'student_id' => $grade->student_id,
            'field_changed' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);
    }
}
