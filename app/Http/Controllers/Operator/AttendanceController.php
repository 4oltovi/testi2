<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CategoryScore;
use App\Models\GradeCategorySetting;
use App\Models\Group;
use App\Models\Semester;
use App\Models\Student;
use App\Models\SubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Рӯйхати гурӯҳҳо барои давомот
     */
    public function index(Request $request): View
    {
        $groups = Group::where('is_active', true)
            ->with(['specialty', 'course'])
            ->withCount('activeStudents')
            ->orderBy('name')
            ->get();

        $date = $request->get('date', now()->format('Y-m-d'));

        return view('operator.attendance.index', compact('groups', 'date'));
    }

    /**
     * Саҳифаи давомот барои як гурӯҳ дар як рӯз
     */
    public function group(Group $group, Request $request): View
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $students = $group->activeStudents()->with('user')->get()->sortBy('user.last_name');

        // Давомоти мавҷуда барои ин рӯз
        $existingAttendance = Attendance::where('lesson_date', $date)
            ->whereHas('subjectAssignment', function ($q) use ($group) {
                $q->where('group_id', $group->id);
            })
            ->pluck('status', 'student_id');

        // Давомоти оператор (simplified: ҳозир/ғоиб дар рӯз)
        $dailyAttendance = DB::table('daily_attendance')
            ->where('group_id', $group->id)
            ->where('attendance_date', $date)
            ->pluck('status', 'student_id');

        return view('operator.attendance.group', compact('group', 'students', 'date', 'dailyAttendance'));
    }

    /**
     * Сабти давомоти рӯзона (як бор дар рӯз: ҳозир/ғоиб)
     * Агар ғоиб → ҳамаи дарсҳои он рӯзро 0 мегузорад
     */
    public function store(Group $group, Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent',
        ]);

        $date = $request->input('date');
        $semester = Semester::current();

        DB::transaction(function () use ($group, $request, $date, $semester) {
            foreach ($request->input('attendance') as $studentId => $status) {
                // Сабт дар daily_attendance
                DB::table('daily_attendance')->updateOrInsert(
                    [
                        'student_id' => $studentId,
                        'group_id' => $group->id,
                        'attendance_date' => $date,
                    ],
                    [
                        'status' => $status,
                        'marked_by' => auth()->id(),
                        'updated_at' => now(),
                    ]
                );

                // Агар ҒОИБ → ҳамаи category_scores-и ин рӯзро 0 мекунем
                if ($status === 'absent') {
                    $this->setZeroScoresForAbsentStudent($studentId, $group->id, $date, $semester?->id);
                }
            }
        });

        return back()->with('success', 'Давомот сабт шуд. Ғоибон дар ҳамаи дарсҳои имрӯз 0 гирифтанд.');
    }

    /**
     * Барои донишҷӯи ғоиб ҳамаи баҳоҳои он рӯзро 0 мегузорад
     */
    private function setZeroScoresForAbsentStudent(int $studentId, int $groupId, string $date, ?int $semesterId): void
    {
        if (!$semesterId) return;

        // Ҳамаи фанҳои ин гурӯҳ дар ин семестр
        $assignments = SubjectAssignment::where('group_id', $groupId)
            ->where('semester_id', $semesterId)
            ->where('is_active', true)
            ->get();

        foreach ($assignments as $assignment) {
            $categorySettings = GradeCategorySetting::getOrCreateDefaults($assignment->id);

            // Барои ҳар категория 0 мегузорем (lesson_number = 1 default)
            foreach ($categorySettings->where('is_active', true) as $cs) {
                $catValue = $cs->category instanceof \App\Enums\GradeCategory
                    ? $cs->category->value
                    : $cs->category;

                CategoryScore::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_assignment_id' => $assignment->id,
                        'lesson_date' => $date,
                        'lesson_number' => 1,
                        'category' => $catValue,
                    ],
                    [
                        'semester_id' => $semesterId,
                        'score' => 0,
                        'max_score' => $cs->max_score,
                        'graded_by' => auth()->id(),
                    ]
                );
            }
        }
    }
}
