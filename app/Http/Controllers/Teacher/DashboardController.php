<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $teacher = $user->teacher;
        $semester = Semester::current();

        $data = [
            'teacher' => $teacher,
            'semester' => $semester,
            'assignments' => collect(),
            'weekly_hours' => 0,
            'groups_count' => 0,
        ];

        if ($teacher && $semester) {
            $assignments = $teacher->getSubjectsInSemester($semester->id);
            $data['assignments'] = $assignments;
            $data['weekly_hours'] = $assignments->sum('hours_per_week');
            $data['groups_count'] = $assignments->pluck('group_id')->unique()->count();
        }

        return view('teacher.dashboard', $data);
    }
}
