<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        $stats = [
            'total_students' => 0,
            'total_teachers' => 0,
            'total_groups' => 0,
            'total_faculties' => 0,
            'total_users' => 0,
            'students_with_debts' => 0,
            'active_debts' => 0,
        ];

        try {
            $stats['total_students'] = \App\Models\Student::where('status', 'active')->count();
            $stats['total_teachers'] = \App\Models\Teacher::where('status', 'active')->count();
            $stats['total_groups'] = \App\Models\Group::where('is_active', true)->count();
            $stats['total_faculties'] = \App\Models\Faculty::where('is_active', true)->count();
            $stats['total_users'] = \App\Models\User::where('status', 'active')->count();
            $stats['students_with_debts'] = \App\Models\Student::where('has_debts', true)->count();
            $stats['active_debts'] = \App\Models\AcademicDebt::whereIn('status', ['active', 'retake_scheduled', 'escalated'])->count();
        } catch (\Throwable $e) {
            // skip
        }

        return view('admin.dashboard.index', compact('stats', 'user'));
    }
}
