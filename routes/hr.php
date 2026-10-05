<?php

use App\Http\Controllers\Hr\HrDashboardController;
use App\Http\Controllers\Hr\HrEmployeeController;
use App\Http\Controllers\Hr\HrStudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Кадр (HR)
|--------------------------------------------------------------------------
| Рӯйҳои Кадр АКСТАН аз auth + role:hr пайгирӣ мешаванд.
|
| Сабаби ин аст, ки гурӯҳи `admin.php` танҳо middleware-и `web` дорад ва
| /admin/* аз рӯи номкор будан дастрап мебуд. Агар танҳо ба гурӯҳ таваанӣ
| мекардем, супориш аз роҳҳои HR ба корбари ворид мегузашт. Ҳамаи роҳҳо дар
| ин файл аз нав middleware-и худро мегиранд.
|
| Ҳар як action-и муҳим (таҳрир, иваз нақш, фаъолсозӣ) боз ҳам дар
| `HrPolicy` санҷида мешавад — middleware танҳо дохили панелро пинҳон
| мекунад, ки хатои ҷойӣ ҳам ба 403 берд.
|
| Ҳеҷ роҳи DELETE барои корманд нест: нест кардан аз ҷои худ фаъол/ғайрифаъол
| кардан бехатартар аст.
*/

Route::middleware(['web', 'auth', 'role:hr'])
    ->prefix('hr')
    ->name('hr.')
    ->group(function () {

        // Панели асосӣ
        Route::get('/dashboard', [HrDashboardController::class, 'index'])->name('dashboard');

        // ---------------- Корманд ----------------
        Route::get('/employees', [HrEmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [HrEmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [HrEmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [HrEmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/edit', [HrEmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [HrEmployeeController::class, 'update'])->name('employees.update');

        // Фаъол ⇄ ғайрифаъол (DELETE мавҷуд нест — ҳаққи нест кардан надорад)
        Route::patch('/employees/{employee}/status', [HrEmployeeController::class, 'toggleStatus'])->name('employees.status');

        // ---------------- Донишҷӯён (танҳо хондан) ----------------
        //
        // Ҳеҷ роҳи POST/PUT/PATCH/DELETE дар ин база намешавад: Кадр нақши
        // сабт, таҳрир, гузаронидан ё нест кардани донишҷӯро надорад. Агар
        // рӯе аз ин ба зудда карда шавад, уҳ бар кад аз он манъ аст.
        Route::get('/students', [HrStudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [HrStudentController::class, 'show'])->name('students.show');
    });