<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Саҳифаи login
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect($this->redirectByRole(Auth::user()));
        }
        return view('auth.login');
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = ['login' => $request->login, 'password' => $request->password];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect мутобиқи role
            return redirect()->intended($this->redirectByRole($user));
        }

        return back()->withErrors(['login' => 'Логин ё парол нодуруст аст.'])->withInput($request->only('login'));
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /**
     * Redirect мутобиқи нақши корбар
     */
    private function redirectByRole($user): string
    {
        $topRole = $user->roles()->orderByDesc('level')->first();

        if (!$topRole) {
            return '/student/dashboard';
        }

        return match ($topRole->name) {
            'super_admin', 'admin' => '/admin/dashboard',
            'teacher' => '/teacher/dashboard',
            'operator' => '/operator/attendance',
            'dean', 'vice_dean', 'department_head', 'registrar', 'accountant' => '/management/dashboard',
            default => '/student/dashboard',
        };
    }
}
