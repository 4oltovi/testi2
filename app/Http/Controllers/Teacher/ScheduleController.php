<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Semester;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $semester = Semester::current();
        $schedules = Schedule::whereHas('subjectAssignment', fn($q) => $q->where('teacher_id', $request->user()->id)->when($semester, fn($q2) => $q2->where('semester_id', $semester->id)))
            ->with(['subjectAssignment.subject', 'subjectAssignment.group', 'classroom'])
            ->orderBy('day_of_week')
            ->orderBy('lesson_number')
            ->get();

        return view('teacher.schedule', compact('schedules', 'semester'));
    }
}
