<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        $role = $user->getPrimaryRoleAttribute();

        $stats = [
            'total_students' => 0,
            'total_teachers' => 0,
            'total_groups' => 0,
            'total_faculties' => 0,
            'students_with_debts' => 0,
            'active_debts' => 0,
            'recent_students' => collect(),
            'recent_debts' => collect(),
        ];

        try {
            $stats['total_students'] = Student::where('status', 'active')->count();
            $stats['total_teachers'] = Teacher::where('status', 'active')->count();
            $stats['total_groups'] = Group::where('is_active', true)->count();
            $stats['total_faculties'] = \App\Models\Faculty::where('is_active', true)->count();
            $stats['students_with_debts'] = Student::where('has_debts', true)->count();
            $stats['active_debts'] = AcademicDebt::whereIn('status', ['active', 'retake_scheduled', 'escalated'])->count();

            $stats['recent_students'] = Student::with('user', 'group')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $stats['recent_debts'] = AcademicDebt::with('student.user')
                ->whereIn('status', ['active', 'retake_scheduled', 'escalated'])
                ->orderByDesc('debt_date')
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            $stats = array_map(fn($v) => is_numeric($v) ? 0 : $v, $stats);
        }

        return view('management.dashboard.index', compact('stats', 'user', 'role'));
    }
}
