<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Масирҳои Панели Идоракунӣ
|--------------------------------------------------------------------------
| Ҳамаи route-ҳо дар middleware('web') — барои session/cookie
| Auth дар контроллерҳо чек мешавад
*/

Route::middleware(['web'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Корбарон
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/block', [UserController::class, 'block'])->name('users.block');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Сохтор
    Route::prefix('structure')->name('structure.')->group(function () {
        Route::resource('faculties', \App\Http\Controllers\Admin\FacultyController::class);
        Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class);
        Route::resource('specialties', \App\Http\Controllers\Admin\SpecialtyController::class);
        Route::resource('groups', \App\Http\Controllers\Admin\GroupController::class);
        Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class);
        Route::resource('classrooms', \App\Http\Controllers\Admin\ClassroomController::class);
        Route::resource('academic-years', \App\Http\Controllers\Admin\AcademicYearController::class);
        Route::resource('curriculum', \App\Http\Controllers\Admin\CurriculumController::class);
    });

    // Донишҷӯён
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\StudentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\StudentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\StudentController::class, 'store'])->name('store');
        Route::get('/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'show'])->name('show');
        Route::get('/{student}/edit', [\App\Http\Controllers\Admin\StudentController::class, 'edit'])->name('edit');
        Route::put('/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'update'])->name('update');
        Route::post('/{student}/change-status', [\App\Http\Controllers\Admin\StudentController::class, 'changeStatus'])->name('change-status');
        Route::post('/{student}/promote', [\App\Http\Controllers\Admin\StudentController::class, 'promote'])->name('promote');
    });

    // Омӯзгорон
    Route::prefix('teachers')->name('teachers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TeacherController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\TeacherController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\TeacherController::class, 'store'])->name('store');
        Route::get('/{teacher}', [\App\Http\Controllers\Admin\TeacherController::class, 'show'])->name('show');
        Route::get('/{teacher}/edit', [\App\Http\Controllers\Admin\TeacherController::class, 'edit'])->name('edit');
        Route::put('/{teacher}', [\App\Http\Controllers\Admin\TeacherController::class, 'update'])->name('update');
    });

    // Журнал
    Route::prefix('journal')->name('journal.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\JournalController::class, 'index'])->name('index');
        Route::get('/assignments/create', [\App\Http\Controllers\Admin\JournalController::class, 'createAssignment'])->name('assignments.create');
        Route::post('/assignments', [\App\Http\Controllers\Admin\JournalController::class, 'storeAssignment'])->name('assignments.store');
        Route::get('/attendance/{subjectAssignment}', [\App\Http\Controllers\Admin\JournalController::class, 'attendance'])->name('attendance');
        Route::post('/attendance/{subjectAssignment}', [\App\Http\Controllers\Admin\JournalController::class, 'storeAttendance'])->name('attendance.store');
        Route::get('/grades/{subjectAssignment}', [\App\Http\Controllers\Admin\JournalController::class, 'grades'])->name('grades');
        Route::post('/grades/{subjectAssignment}', [\App\Http\Controllers\Admin\JournalController::class, 'storeGrades'])->name('grades.store');
        Route::get('/semester-grades/{subjectAssignment}', [\App\Http\Controllers\Admin\JournalController::class, 'semesterGrades'])->name('semester-grades');
        Route::post('/finalize/{semesterGrade}', [\App\Http\Controllers\Admin\JournalController::class, 'finalize'])->name('finalize');

        // Категорияҳои баҳо (5 категория: Савод, Сарулибос, Ҷиҳоз, Иштирок, Интизом)
        Route::get('/category-scores/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'index'])->name('category-scores');
        Route::post('/category-scores/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'store'])->name('category-scores.store');
        Route::get('/category-settings/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'settings'])->name('category-settings');
        Route::put('/category-settings/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'updateSettings'])->name('category-settings.update');
        Route::get('/category-report/{subjectAssignment}', [\App\Http\Controllers\Teacher\CategoryScoreController::class, 'report'])->name('category-report');
    });

    // Рейтингҳо
    Route::prefix('ratings')->name('ratings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RatingController::class, 'index'])->name('index');
        Route::get('/group/{group}', [\App\Http\Controllers\Admin\RatingController::class, 'group'])->name('group');
        Route::get('/faculty/{faculty}', [\App\Http\Controllers\Admin\RatingController::class, 'faculty'])->name('faculty');
        Route::get('/top-students', [\App\Http\Controllers\Admin\RatingController::class, 'topStudents'])->name('top-students');
    });

    // Имтиҳон
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ExamController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ExamController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ExamController::class, 'store'])->name('store');

        // Саволномаҳо (ПЕШИ /{exam} бошад!)
        Route::resource('question-banks', \App\Http\Controllers\Admin\QuestionBankController::class);
        Route::resource('questions', \App\Http\Controllers\Admin\QuestionController::class)->except(['show']);
        Route::get('/questions-import', [\App\Http\Controllers\Admin\QuestionController::class, 'importForm'])->name('questions.import-form');
        Route::post('/questions-import', [\App\Http\Controllers\Admin\QuestionController::class, 'import'])->name('questions.import');
        Route::get('/questions-template', [\App\Http\Controllers\Admin\QuestionController::class, 'downloadTemplate'])->name('questions.download-template');

        // Имтиҳони мушаххас (бо ID)
        Route::get('/{exam}/edit', [\App\Http\Controllers\Admin\ExamController::class, 'edit'])->name('edit');
        Route::put('/{exam}', [\App\Http\Controllers\Admin\ExamController::class, 'update'])->name('update');
        Route::get('/{exam}', [\App\Http\Controllers\Admin\ExamController::class, 'show'])->name('show');
        Route::get('/{exam}/questions', [\App\Http\Controllers\Admin\ExamController::class, 'questions'])->name('exam-questions');
        Route::post('/{exam}/questions', [\App\Http\Controllers\Admin\ExamController::class, 'addQuestions'])->name('exam-questions.add');
        Route::get('/{exam}/results', [\App\Http\Controllers\Admin\ExamController::class, 'results'])->name('results');
        Route::post('/{exam}/publish', [\App\Http\Controllers\Admin\ExamController::class, 'publish'])->name('publish');
    });

    // Қарздорӣ
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DebtController::class, 'index'])->name('index');
        Route::get('/{debt}', [\App\Http\Controllers\Admin\DebtController::class, 'show'])->name('show');
        Route::post('/{debt}/schedule-retake', [\App\Http\Controllers\Admin\DebtController::class, 'scheduleRetake'])->name('schedule-retake');
        Route::post('/{debt}/resolve', [\App\Http\Controllers\Admin\DebtController::class, 'resolve'])->name('resolve');
        Route::post('/{debt}/escalate', [\App\Http\Controllers\Admin\DebtController::class, 'escalate'])->name('escalate');
    });

    // Transcript
    Route::prefix('transcript')->name('transcript.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TranscriptController::class, 'index'])->name('index');
        Route::get('/student/{student}', [\App\Http\Controllers\Admin\TranscriptController::class, 'show'])->name('show');
        Route::post('/student/{student}/generate', [\App\Http\Controllers\Admin\TranscriptController::class, 'generate'])->name('generate');
        Route::get('/{transcript}/pdf', [\App\Http\Controllers\Admin\TranscriptController::class, 'exportPdf'])->name('pdf');
    });

    // Ҳисоботҳо
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
        Route::get('/students', [\App\Http\Controllers\Admin\ReportController::class, 'students'])->name('students');
        Route::get('/debtors', [\App\Http\Controllers\Admin\ReportController::class, 'debtors'])->name('debtors');
        Route::get('/attendance', [\App\Http\Controllers\Admin\ReportController::class, 'attendance'])->name('attendance');
        Route::get('/gpa', [\App\Http\Controllers\Admin\ReportController::class, 'gpa'])->name('gpa');
        Route::get('/exam-results', [\App\Http\Controllers\Admin\ReportController::class, 'examResults'])->name('exam-results');
        Route::get('/export/{type}', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('export');
    });

    // Танзимот (Формулаҳо ва Тест)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('update');
        Route::get('/formula', [\App\Http\Controllers\Admin\SettingsController::class, 'formula'])->name('formula');
        Route::get('/test', [\App\Http\Controllers\Admin\SettingsController::class, 'test'])->name('test');
    });

    // Импорти Excel
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ImportController::class, 'index'])->name('index');
        Route::get('/template', [\App\Http\Controllers\Admin\ImportController::class, 'downloadTemplate'])->name('template');
        Route::post('/students', [\App\Http\Controllers\Admin\ImportController::class, 'importStudents'])->name('students');
    });

    // Аудит
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('index');
    });
});
