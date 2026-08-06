<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    /**
     * Саҳифаи иваз кардани парол (force)
     */
    public function showForm(): View
    {
        return view('auth.change-password');
    }

    /**
     * Иваз кардани парол
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ], [
            'password.required' => 'Пароли нав ҳатмӣ аст.',
            'password.min' => 'Парол бояд ҳадди ақал 4 рамз бошад.',
            'password.confirmed' => 'Тасдиқи парол мувофиқат намекунад.',
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        // Redirect мутобиқи role
        $redirectTo = match(true) {
            $user->hasRole('admin') => '/admin/dashboard',
            $user->hasRole('teacher') => '/teacher/dashboard',
            $user->hasRole('student') => '/student/dashboard',
            $user->hasRole('operator') => '/operator/attendance',
            default => '/',
        };

        return redirect($redirectTo)->with('success', 'Парол бо муваффақият иваз шуд!');
    }
}
