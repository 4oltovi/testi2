<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:dean,vice_dean,department_head,registrar,accountant'])
    ->prefix('management')
    ->name('management.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Management\DashboardController::class, 'index'])->name('dashboard');
    });
