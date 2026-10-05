<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Accountant\AccountantDashboardController;

Route::middleware(['web', 'auth', 'role:accountant'])
    ->prefix('accountant')
    ->name('accountant.')
    ->group(function () {
        Route::get('/dashboard', [AccountantDashboardController::class, 'index'])->name('dashboard');
        Route::get('/students', [AccountantDashboardController::class, 'students'])->name('students');
        Route::get('/students/{student}', [AccountantDashboardController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/payments', [AccountantDashboardController::class, 'storePayment'])->name('payments.store');
        Route::get('/reports/groups', [AccountantDashboardController::class, 'groupReport'])->name('reports.groups');
    });