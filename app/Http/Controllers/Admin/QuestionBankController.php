<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Http\Request;

class QuestionBankController extends Controller
{
    public function index()
    {
        $banks = QuestionBank::with(['subject', 'teacher'])->latest()->paginate(20);
        return view('admin.exams.question-banks.index', compact('banks'));
    }

    public function create()
    {
        $subjects = Subject::active()->orderBy('name')->get();
        return view('admin.exams.question-banks.create', compact('subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        QuestionBank::create([...$validated, 'teacher_id' => auth()->id(), 'is_active' => true]);

        return redirect()->route('admin.exams.question-banks.index')->with('success', 'Банки саволҳо сохта шуд.');
    }

    public function show(QuestionBank $questionBank)
    {
        $questionBank->load('questions');
        return view('admin.exams.question-banks.show', compact('questionBank'));
    }

    public function edit(QuestionBank $questionBank)
    {
        $subjects = Subject::active()->orderBy('name')->get();
        return view('admin.exams.question-banks.edit', compact('questionBank', 'subjects'));
    }

    public function update(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $questionBank->update($validated);
        return redirect()->route('admin.exams.question-banks.index')->with('success', 'Навсозӣ шуд.');
    }

    public function destroy(QuestionBank $questionBank)
    {
        $questionBank->delete();
        return redirect()->route('admin.exams.question-banks.index')->with('success', 'Нест шуд.');
    }
}
