<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

// ====== ТЕСТ (баъдтар нест кунед) ======
Route::get('/whoami', function () {
    if (auth()->check()) {
        $u = auth()->user();
        return "LOGGED IN: {$u->login} ({$u->first_name} {$u->last_name}) | Session: " . session()->getId();
    }
    return "NOT LOGGED IN | Session: " . session()->getId();
});

// Саҳифаи асосӣ
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return redirect('/admin/dashboard');
        } elseif ($user->hasRole('teacher')) {
            return redirect('/teacher/dashboard');
        } elseif ($user->hasRole('student')) {
            return redirect('/student/dashboard');
        } elseif ($user->hasRole('operator')) {
            return redirect('/operator/attendance');
        }
        return redirect('/login');
    }
    return redirect('/login');
});

// AUTH
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Парол
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'change'])->name('password.update');

    // Force change password (барои донишҷӯёни нав бо парол 12345678)
    Route::get('/change-password', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'showForm'])->name('password.force-change');
    Route::post('/change-password', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'update'])->name('password.force-change.update');
});
