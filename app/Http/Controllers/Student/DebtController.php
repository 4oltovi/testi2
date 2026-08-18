<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicDebt;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;

        $debts = AcademicDebt::where('student_id', $student?->id)
            ->with(['semesterGrade.subject', 'semesterGrade.semester'])
            ->orderByDesc('created_at')
            ->get();

        return view('student.debts', compact('debts', 'student'));
    }
}
