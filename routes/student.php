<?php

use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ProfileController;
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

    // Профил
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

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

    // Давомот
    Route::get('/attendance', [\App\Http\Controllers\Student\AttendanceController::class, 'index'])->name('attendance');

    // Қарздориҳо
    Route::get('/debts', [\App\Http\Controllers\Student\DebtController::class, 'index'])->name('debts');

    // ==================== РЕЙТИНГИ ОНЛАЙН ====================
    Route::prefix('rating')->name('rating.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Student\RatingController::class, 'index'])->name('index');
        Route::post('/start/{ratingSession}/{subject}', [\App\Http\Controllers\Student\RatingController::class, 'start'])->name('start');
        Route::get('/take/{ratingAttempt}', [\App\Http\Controllers\Student\RatingController::class, 'take'])->name('take');
        Route::post('/submit/{ratingAttempt}', [\App\Http\Controllers\Student\RatingController::class, 'submit'])->name('submit');
    });
});
