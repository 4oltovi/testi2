<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Масирҳои Оператор
|--------------------------------------------------------------------------
| Middleware: auth, role:operator
| Prefix: /operator
| Name: operator.
*/

Route::middleware(['web', 'auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {

    // Давомоти рӯзона
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Operator\AttendanceController::class, 'index'])->name('index');
        Route::get('/group/{group}', [\App\Http\Controllers\Operator\AttendanceController::class, 'group'])->name('group');
        Route::post('/group/{group}', [\App\Http\Controllers\Operator\AttendanceController::class, 'store'])->name('store');
    });

});
