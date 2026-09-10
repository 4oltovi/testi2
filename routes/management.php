<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:dean,vice_dean,department_head,registrar,accountant', 'dean.readonly'])
    ->prefix('management')
    ->name('management.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Management\DashboardController::class, 'index'])->name('dashboard');

        // Донишҷӯён (View + Search только)
        Route::get('/students', [\App\Http\Controllers\Management\StudentController::class, 'index'])->name('students.index');
        Route::get('/students/search', [\App\Http\Controllers\Management\StudentController::class, 'search'])->name('students.search');
        Route::get('/students/{student}', [\App\Http\Controllers\Management\StudentController::class, 'show'])->name('students.show');

        // Омӯзгорон (View only)
        Route::get('/teachers', [\App\Http\Controllers\Management\TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/{teacher}', [\App\Http\Controllers\Management\TeacherController::class, 'show'])->name('teachers.show');

        // Ихтисосҳо (View only)
        Route::get('/specialties', [\App\Http\Controllers\Management\SpecialtyController::class, 'index'])->name('specialties.index');
        Route::get('/specialties/{specialty}', [\App\Http\Controllers\Management\SpecialtyController::class, 'show'])->name('specialties.show');

        // Гурӯҳҳо (View only)
        Route::get('/groups', [\App\Http\Controllers\Management\GroupController::class, 'index'])->name('groups.index');
        Route::get('/groups/{group}', [\App\Http\Controllers\Management\GroupController::class, 'show'])->name('groups.show');

        // Фанҳо (View only)
        Route::get('/subjects', [\App\Http\Controllers\Management\SubjectController::class, 'index'])->name('subjects.index');
        Route::get('/subjects/{subject}', [\App\Http\Controllers\Management\SubjectController::class, 'show'])->name('subjects.show');

        // Журнал
        Route::get('/journal', [\App\Http\Controllers\Management\JournalController::class, 'assignments'])->name('journal.index');

        Route::get('/journal/grades/{subjectAssignment}', [\App\Http\Controllers\Management\JournalController::class, 'grades'])->name('journal.grades');
        Route::get('/journal/semester-grades/{subjectAssignment}', [\App\Http\Controllers\Management\JournalController::class, 'semesterGrades'])->name('journal.semester-grades');
        Route::get('/journal/attendance/{subjectAssignment}', [\App\Http\Controllers\Management\JournalController::class, 'attendance'])->name('journal.attendance');

        // Қарздорӣ (View only)
        Route::get('/debts', [\App\Http\Controllers\Management\DebtController::class, 'index'])->name('debts.index');
        Route::get('/debts/{debt}', [\App\Http\Controllers\Management\DebtController::class, 'show'])->name('debts.show');

        // Ҳисоботҳо
        Route::get('/reports', [\App\Http\Controllers\Management\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/students', [\App\Http\Controllers\Management\ReportController::class, 'students'])->name('reports.students');
        Route::get('/reports/debtors', [\App\Http\Controllers\Management\ReportController::class, 'debtors'])->name('reports.debtors');
        Route::get('/reports/attendance', [\App\Http\Controllers\Management\ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/gpa', [\App\Http\Controllers\Management\ReportController::class, 'gpa'])->name('reports.gpa');
        Route::get('/reports/exam-results', [\App\Http\Controllers\Management\ReportController::class, 'examResults'])->name('reports.exam-results');
        Route::get('/reports/export/{type}', [\App\Http\Controllers\Management\ReportController::class, 'export'])->name('reports.export');
    });
