<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->format('Y-m-d');
        $semester = Semester::current();

        $groupId = $request->get('group_id');
        $specialtyId = $request->get('specialty_id');
        $facultyId = $request->get('faculty_id');
        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', $today);
        $trendDays = (int) $request->get('trend_days', 7);

        $groupsQuery = Group::where('is_active', true)->with('specialty.department.faculty')->orderBy('name');
        if ($facultyId) {
            $groupsQuery->whereHas('specialty.department', fn($q) => $q->where('faculty_id', $facultyId));
        }
        if ($specialtyId) {
            $groupsQuery->where('specialty_id', $specialtyId);
        }
        $groups = $groupsQuery->get();

        $groupIds = $groupId ? [$groupId] : $groups->pluck('id')->toArray();

        $summary = Cache::remember("attendance_summary_{$today}_" . implode(',', $groupIds), 300, function () use ($groupIds, $today) {
            if (empty($groupIds)) {
                return ['total' => 0, 'present' => 0, 'absent' => 0, 'percentage' => 0, 'groups_marked' => 0];
            }

            $stats = DB::table('daily_attendance')
                ->where('attendance_date', $today)
                ->whereIn('group_id', $groupIds)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent,
                    COUNT(DISTINCT group_id) as groups_marked
                ')
                ->first();

            $total = (int) ($stats->total ?? 0);
            $present = (int) ($stats->present ?? 0);
            $absent = (int) ($stats->absent ?? 0);
            $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            return [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'percentage' => $percentage,
                'groups_marked' => (int) ($stats->groups_marked ?? 0),
            ];
        });

        $trendData = Cache::remember("attendance_trend_{$trendDays}_" . implode(',', $groupIds), 300, function () use ($groupIds, $trendDays, $today) {
            if (empty($groupIds)) return collect();

            return DB::table('daily_attendance')
                ->whereBetween('attendance_date', [now()->subDays($trendDays - 1)->format('Y-m-d'), $today])
                ->whereIn('group_id', $groupIds)
                ->selectRaw('
                    attendance_date,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent
                ')
                ->groupBy('attendance_date')
                ->orderBy('attendance_date')
                ->get();
        });

        $groupStats = Cache::remember("attendance_group_stats_30_{$today}_" . implode(',', $groupIds), 300, function () use ($groupIds, $today) {
            if (empty($groupIds)) return collect();

            return DB::table('daily_attendance')
                ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
                ->whereBetween('daily_attendance.attendance_date', [now()->subDays(30)->format('Y-m-d'), $today])
                ->whereIn('daily_attendance.group_id', $groupIds)
                ->selectRaw('
                    groups.id,
                    groups.name,
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
        });

        $lowAttendanceStudents = DB::table('daily_attendance')
            ->join('students', 'daily_attendance.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
            ->whereBetween('daily_attendance.attendance_date', [now()->subDays(30)->format('Y-m-d'), $today])
            ->whereIn('daily_attendance.group_id', $groupIds)
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
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $item->percentage = $item->total > 0 ? round(($item->present / $item->total) * 100, 1) : 0;
                return $item;
            });

        $totalStudents = Student::where('status', 'active')
            ->when($groupId, fn($q) => $q->where('group_id', $groupId))
            ->when($specialtyId, fn($q) => $q->whereHas('group', fn($qq) => $qq->where('specialty_id', $specialtyId)))
            ->count();

        $specialties = \App\Models\Specialty::with('department.faculty')->where('is_active', true)->orderBy('name')->get();
        $faculties = \App\Models\Faculty::orderBy('name')->get();

        return view('admin.attendance.index', compact(
            'summary', 'trendData', 'groupStats', 'lowAttendanceStudents', 'totalStudents',
            'today', 'startDate', 'endDate', 'trendDays',
            'groups', 'groupIds', 'groupId', 'specialtyId', 'facultyId',
            'specialties', 'faculties', 'semester'
        ));
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $groupId = $request->get('group_id');

        $query = DB::table('daily_attendance')
            ->join('students', 'daily_attendance.student_id', '=', 'students.id')
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select(
                'daily_attendance.attendance_date',
                DB::raw("CONCAT(users.last_name, ' ', users.first_name) as student_name"),
                'groups.name as group_name',
                'daily_attendance.status',
                'students.student_id_number'
            )
            ->whereBetween('daily_attendance.attendance_date', [$startDate, $endDate])
            ->orderByDesc('daily_attendance.attendance_date')
            ->orderBy('users.last_name');

        if ($groupId) {
            $query->where('daily_attendance.group_id', $groupId);
        }

        $records = $query->get();

        $rows = $records->map(function ($r) {
            return [
                'Сана' => $r->attendance_date,
                'Донишҷӯ' => $r->student_name,
                'Шиноса' => $r->student_id_number,
                'Гурӯҳ' => $r->group_name,
                'Ҳолат' => $r->status === 'present' ? 'Ҳозир' : 'Ғоиб',
            ];
        })->values();

        \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\AttendanceReportExport($rows), 'attendance_report_' . $startDate . '_' . $endDate . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $groupId = $request->get('group_id');

        $query = DB::table('daily_attendance')
            ->join('students', 'daily_attendance.student_id', '=', 'students.id')
            ->join('groups', 'daily_attendance.group_id', '=', 'groups.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select(
                'daily_attendance.attendance_date',
                DB::raw("CONCAT(users.last_name, ' ', users.first_name) as student_name"),
                'groups.name as group_name',
                'daily_attendance.status',
                'students.student_id_number'
            )
            ->whereBetween('daily_attendance.attendance_date', [$startDate, $endDate])
            ->orderByDesc('daily_attendance.attendance_date')
            ->orderBy('users.last_name');

        if ($groupId) {
            $query->where('daily_attendance.group_id', $groupId);
        }

        $records = $query->get();
        $summary = [
            'total' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
        ];
        $summary['percentage'] = $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100, 1) : 0;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.attendance.export-pdf', compact('records', 'summary', 'startDate', 'endDate'));
        $pdf->setPaper('a4');

        return $pdf->download('attendance_report_' . $startDate . '_' . $endDate . '.pdf');
    }
}
