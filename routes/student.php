<?php

use App\Http\Controllers\Student\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Масирҳои Донишҷӯ
|--------------------------------------------------------------------------
| Middleware: auth, role:student
| Prefix: /student
| Name: student.
*/

Route::middleware(['web', 'auth', 'role:student'])->prefix('student')->name('student.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Баҳоҳо ва рейтингҳо
    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Student\GradeController::class, 'index'])->name('index');
        Route::get('/semester/{semester}', [\App\Http\Controllers\Student\GradeController::class, 'semester'])->name('semester');
    });

    // Имтиҳонҳо ва Тестҳо
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Student\ExamController::class, 'index'])->name('index');
        Route::post('/{exam}/start', [\App\Http\Controllers\Student\ExamController::class, 'start'])->name('start');
        Route::get('/{exam}/take/{attempt}', [\App\Http\Controllers\Student\ExamController::class, 'take'])->name('take');
        Route::post('/{exam}/save-answer/{attempt}', [\App\Http\Controllers\Student\ExamController::class, 'saveAnswer'])->name('save-answer');
        Route::post('/{exam}/submit/{attempt}', [\App\Http\Controllers\Student\ExamController::class, 'submit'])->name('submit');
        Route::get('/{exam}/result/{attempt}', [\App\Http\Controllers\Student\ExamController::class, 'result'])->name('result');
    });

    // Transcript
    Route::prefix('transcript')->name('transcript.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Student\TranscriptController::class, 'index'])->name('index');
        Route::get('/download', [\App\Http\Controllers\Student\TranscriptController::class, 'download'])->name('download');
    });

    // Ҷадвали дарс
    Route::get('/schedule', [\App\Http\Controllers\Student\ScheduleController::class, 'index'])->name('schedule');

    // Қарздориҳо
    Route::get('/debts', [\App\Http\Controllers\Student\DebtController::class, 'index'])->name('debts');
});
