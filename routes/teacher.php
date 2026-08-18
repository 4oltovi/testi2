<?php

use App\Http\Controllers\Teacher\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Масирҳои Омӯзгор
|--------------------------------------------------------------------------
| Middleware: auth, role:teacher
| Prefix: /teacher
| Name: teacher.
*/

Route::middleware(['web', 'auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Журнали электронӣ
    Route::prefix('journal')->name('journal.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Teacher\JournalController::class, 'index'])->name('index');
        Route::get('/attendance/{subjectAssignment}', [\App\Http\Controllers\Teacher\JournalController::class, 'attendance'])->name('attendance');
        Route::post('/attendance/{subjectAssignment}', [\App\Http\Controllers\Teacher\JournalController::class, 'storeAttendance'])->name('attendance.store');
        Route::get('/grades/{subjectAssignment}', [\App\Http\Controllers\Teacher\JournalController::class, 'grades'])->name('grades');
        Route::post('/grades/{subjectAssignment}', [\App\Http\Controllers\Teacher\JournalController::class, 'storeGrades'])->name('grades.store');
        Route::get('/semester-grades/{subjectAssignment}', [\App\Http\Controllers\Teacher\JournalController::class, 'semesterGrades'])->name('semester-grades');

        // Журнали категориявӣ (5 категория: Савод, Сарулибос, Ҷиҳоз, Иштирок, Интизом)
        Route::get('/category-scores/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'index'])->name('category-scores');
        Route::post('/category-scores/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'store'])->name('category-scores.store');
        Route::get('/category-settings/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'settings'])->name('category-settings');
        Route::put('/category-settings/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'updateSettings'])->name('category-settings.update');
        Route::get('/category-report/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'report'])->name('category-report');
    });

    // Имтиҳонҳо
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Teacher\ExamController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Teacher\ExamController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Teacher\ExamController::class, 'store'])->name('store');
        Route::get('/{exam}', [\App\Http\Controllers\Teacher\ExamController::class, 'show'])->name('show');
        Route::get('/{exam}/edit', [\App\Http\Controllers\Teacher\ExamController::class, 'edit'])->name('edit');
        Route::put('/{exam}', [\App\Http\Controllers\Teacher\ExamController::class, 'update'])->name('update');
        Route::get('/{exam}/results', [\App\Http\Controllers\Teacher\ExamController::class, 'results'])->name('results');
        Route::post('/{exam}/publish', [\App\Http\Controllers\Teacher\ExamController::class, 'publish'])->name('publish');
        Route::post('/{exam}/add-questions', [\App\Http\Controllers\Teacher\ExamController::class, 'addQuestions'])->name('add-questions');
        Route::delete('/{exam}/questions/{examQuestion}', [\App\Http\Controllers\Teacher\ExamController::class, 'removeQuestion'])->name('remove-question');
    });

    // Банки саволҳо
    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Teacher\QuestionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Teacher\QuestionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Teacher\QuestionController::class, 'store'])->name('store');
        Route::get('/{question}/edit', [\App\Http\Controllers\Teacher\QuestionController::class, 'edit'])->name('edit');
        Route::put('/{question}', [\App\Http\Controllers\Teacher\QuestionController::class, 'update'])->name('update');
        Route::delete('/{question}', [\App\Http\Controllers\Teacher\QuestionController::class, 'destroy'])->name('destroy');
    });

    // Ҷадвали дарс
    Route::get('/schedule', [\App\Http\Controllers\Teacher\ScheduleController::class, 'index'])->name('schedule');
});
