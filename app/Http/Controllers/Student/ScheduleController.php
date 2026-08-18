<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Semester;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        $semester = Semester::current();

        $schedules = collect();
        if ($student && $semester) {
            $schedules = Schedule::whereHas('subjectAssignment', fn($q) => $q->where('group_id', $student->group_id)->where('semester_id', $semester->id))
                ->with(['subjectAssignment.subject', 'classroom'])
                ->orderBy('day_of_week')
                ->orderBy('lesson_number')
                ->get();
        }

        return view('student.schedule', compact('schedules', 'semester'));
    }
}
