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
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $groups = Group::where('is_active', true)
            ->with(['specialty', 'course'])
            ->withCount('activeStudents')
            ->orderBy('name')
            ->get();

        return view('operator.attendance.index', compact('groups'));
    }

    public function group(Group $group, Request $request): View
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $students = $group->activeStudents()->with('user')->get()->sortBy('user.last_name');

        $dailyAttendance = DB::table('daily_attendance')
            ->where('group_id', $group->id)
            ->where('attendance_date', $date)
            ->pluck('status', 'student_id');

        $summary = [
            'total' => $students->count(),
            'present' => $dailyAttendance->filter(fn($s) => $s === 'present')->count(),
            'absent' => $dailyAttendance->filter(fn($s) => $s === 'absent')->count(),
            'percentage' => $students->count() > 0 ? round(($dailyAttendance->filter(fn($s) => $s === 'present')->count() / $students->count()) * 100, 1) : 0,
        ];

        return view('operator.attendance.group', compact('group', 'students', 'date', 'dailyAttendance', 'summary'));
    }

    public function store(Group $group, Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent',
        ]);

        $date = $request->input('date');
        $attendanceData = $request->input('attendance');
        $semester = Semester::current();

        if (!$semester) {
            return back()->with('error', 'Семестри фаъол ёфта нашуд!');
        }

        $existingAttendance = DB::table('daily_attendance')
            ->where('group_id', $group->id)
            ->where('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        $blockedChanges = [];
        foreach ($attendanceData as $studentId => $newStatus) {
            $existing = $existingAttendance->get($studentId);
            if ($existing && $existing->status === 'absent' && $newStatus === 'present') {
                $blockedChanges[] = $studentId;
            }
        }

        if (!empty($blockedChanges)) {
            return back()->with('error', 'Шумо наметавонед статуси "Ғоиб"-ро ба "Ҳозир" табдил диҳед. Барои тағйир додани ин статус, бо маъмурият муроҷиат кунед.');
        }

        DB::transaction(function () use ($group, $attendanceData, $date, $semester) {
            $dailyAttendanceRecords = [];
            $absentStudentIds = [];
            $presentStudentIds = [];

            foreach ($attendanceData as $studentId => $status) {
                $dailyAttendanceRecords[] = [
                    'student_id' => $studentId,
                    'group_id' => $group->id,
                    'attendance_date' => $date,
                    'status' => $status,
                    'marked_by' => auth()->id(),
                    'updated_at' => now(),
                ];

                if ($status === 'absent') {
                    $absentStudentIds[] = $studentId;
                } else {
                    $presentStudentIds[] = $studentId;
                }
            }

            DB::table('daily_attendance')->upsert(
                $dailyAttendanceRecords,
                ['student_id', 'group_id', 'attendance_date'],
                ['status', 'marked_by', 'updated_at']
            );

            if (!empty($presentStudentIds)) {
                $this->clearZeroScoresForPresentStudents($presentStudentIds, $group->id, $date, $semester->id);
            }

            if (!empty($absentStudentIds)) {
                $this->setZeroScoresForAbsentStudentsBulk($absentStudentIds, $group->id, $date, $semester);
            }
        });

        return back()->with('success', 'Давомот бо муваффақият сабт шуд.');
    }

    /**
     * Оптимизатсияшуда: Сабти 0-ҳо барои ҳамаи ғоибон дар як SQL-запрос
     */
    private function setZeroScoresForAbsentStudentsBulk(array $studentIds, int $groupId, string $date, Semester $semester): void
    {
        $assignments = SubjectAssignment::where('group_id', $groupId)
            ->where('semester_id', $semester->id)
            ->where('is_active', true)
            ->get();

        if ($assignments->isEmpty()) {
            return;
        }

        // Муайян кардани давраи ҷорӣ (rating1 ё rating2)
        $currentPeriod = $semester->getCurrentPeriod() ?? 'rating1';

        $scoresToInsert = [];

        foreach ($assignments as $assignment) {
            $categorySettings = GradeCategorySetting::getOrCreateDefaults($assignment->id);

            foreach ($categorySettings->where('is_active', true) as $cs) {
                $catValue = $cs->category instanceof \App\Enums\GradeCategory
                    ? $cs->category->value
                    : $cs->category;

                foreach ($studentIds as $studentId) {
                    $scoresToInsert[] = [
                        'student_id' => $studentId,
                        'subject_assignment_id' => $assignment->id,
                        'semester_id' => $semester->id,
                        'lesson_date' => $date,
                        'lesson_number' => 1,
                        'category' => $catValue,
                        'period' => $currentPeriod, // Мантиқи динамикии давра
                        'score' => 0,
                        'max_score' => $cs->max_score,
                        'graded_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (!empty($scoresToInsert)) {
            // Иҷрои ягонаи Bulk Upsert ба ҷои даҳҳо SQL Query
            CategoryScore::upsert(
                $scoresToInsert,
                ['student_id', 'subject_assignment_id', 'lesson_date', 'lesson_number', 'category', 'period'],
                ['score', 'max_score', 'graded_by', 'updated_at']
            );
        }
    }

    /**
     * Пок кардани 0-ҳо агар донишҷӯ ҳозир шуда бошад
     */
    private function clearZeroScoresForPresentStudents(array $studentIds, int $groupId, string $date, int $semesterId): void
    {
        $assignmentIds = SubjectAssignment::where('group_id', $groupId)
            ->where('semester_id', $semesterId)
            ->pluck('id');

        CategoryScore::whereIn('student_id', $studentIds)
            ->whereIn('subject_assignment_id', $assignmentIds)
            ->where('lesson_date', $date)
            ->where('score', 0)
            ->delete();
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $groupId = $request->get('group_id');
        $status = $request->get('status', 'all');

        $query = DB::table('daily_attendance')
            ->join('students', 'daily_attendance.student_id', '=', 'students.id')
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select(
                'daily_attendance.*',
                DB::raw("CONCAT(users.last_name, ' ', users.first_name) as student_name"),
                'groups.name as group_name',
                'students.student_id_number'
            )
            ->whereBetween('daily_attendance.attendance_date', [$startDate, $endDate]);

        if ($groupId) {
            $query->where('daily_attendance.group_id', $groupId);
        }
        if ($status === 'absent') {
            $query->where('daily_attendance.status', 'absent');
        } elseif ($status === 'present') {
            $query->where('daily_attendance.status', 'present');
        }

        $records = $query->orderByDesc('daily_attendance.attendance_date')
            ->orderBy('users.last_name')
            ->get();

        return Excel::download(new AttendanceSummaryExport($records, $startDate, $endDate), 'attendance_summary_' . $startDate . '_' . $endDate . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $groupId = $request->get('group_id');
        $status = $request->get('status', 'all');

        $query = DB::table('daily_attendance')
            ->join('students', 'daily_attendance.student_id', '=', 'students.id')
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select(
                'daily_attendance.*',
                DB::raw("CONCAT(users.last_name, ' ', users.first_name) as student_name"),
                'groups.name as group_name',
                'students.student_id_number'
            )
            ->whereBetween('daily_attendance.attendance_date', [$startDate, $endDate]);

        if ($groupId) {
            $query->where('daily_attendance.group_id', $groupId);
        }
        if ($status === 'absent') {
            $query->where('daily_attendance.status', 'absent');
        } elseif ($status === 'present') {
            $query->where('daily_attendance.status', 'present');
        }

        $records = $query->orderByDesc('daily_attendance.attendance_date')
            ->orderBy('users.last_name')
            ->get();

        $pdf = Pdf::loadView('operator.attendance.export-summary-pdf', [
            'records' => $records,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        return $pdf->download('attendance_summary_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function statistics(Request $request): View
    {
        $today = now()->format('Y-m-d');

        $totalStudents = Student::where('status', 'active')->count();

        $todayAttendance = DB::table('daily_attendance')
            ->where('attendance_date', $today)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent
            ')
            ->first();

        $presentToday = $todayAttendance->present ?? 0;
        $absentToday = $todayAttendance->absent ?? 0;

        $presentPercentage = $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 0;
        $absentPercentage = $totalStudents > 0 ? round(($absentToday / $totalStudents) * 100, 1) : 0;

        $todayClasses = DB::table('daily_attendance')
            ->where('attendance_date', $today)
            ->distinct('group_id')
            ->count('group_id');

        $trendDays = $request->get('trend_days', 7);
        $trendData = DB::table('daily_attendance')
            ->whereBetween('attendance_date', [now()->subDays($trendDays - 1)->format('Y-m-d'), $today])
            ->selectRaw('
                attendance_date,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent
            ')
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();

        $groupStats = DB::table('daily_attendance')
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
            ->whereBetween('daily_attendance.attendance_date', [now()->subDays(30)->format('Y-m-d'), $today])
            ->selectRaw('
                groups.id,
                groups.name,
                groups.code,
                CONCAT(groups.name, " ", groups.code) as full_name,
                COUNT(*) as total,
                SUM(CASE WHEN daily_attendance.status = "present" THEN 1 ELSE 0 END) as present
            ')
            ->groupBy('groups.id', 'groups.name')
            ->orderByDesc('present')
            ->get()
            ->map(function ($item) {
                $item->percentage = $item->total > 0 ? round(($item->present / $item->total) * 100, 1) : 0;
                return $item;
            });

        $lowAttendanceStudents = DB::table('daily_attendance')
            ->join('students', 'daily_attendance.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
            ->whereBetween('daily_attendance.attendance_date', [now()->subDays(30)->format('Y-m-d'), $today])
            ->selectRaw('
                students.id,
                CONCAT(users.last_name, " ", users.first_name) as student_name,
                groups.name as group_name,
                students.student_id_number,
                COUNT(*) as total,
                SUM(CASE WHEN daily_attendance.status = "present" THEN 1 ELSE 0 END) as present
            ')
            ->groupBy('students.id', 'users.last_name', 'users.first_name', 'groups.name', 'students.student_id_number')
            ->havingRaw('(SUM(CASE WHEN daily_attendance.status = "present" THEN 1 ELSE 0 END) / COUNT(*)) * 100 < 75')
            ->orderByRaw('(present / total) * 100')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->percentage = $item->total > 0 ? round(($item->present / $item->total) * 100, 1) : 0;
                return $item;
            });

        return view('operator.attendance.statistics', compact(
            'totalStudents', 'presentToday', 'absentToday',
            'presentPercentage', 'absentPercentage', 'todayClasses',
            'trendData', 'groupStats', 'lowAttendanceStudents', 'trendDays'
        ));
    }
}
