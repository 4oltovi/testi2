<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Саҳифаи login
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            $topRole = $user->roles()->orderByDesc('level')->first();

            $dashboardUrl = match ($topRole?->name) {
                'super_admin', 'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                'operator' => '/operator/attendance',
                'accountant' => '/accountant/dashboard',
                'dean', 'vice_dean', 'department_head' => '/management/dashboard',
                'hr' => '/hr/dashboard',
                default => '/student/dashboard',
            };

            return view('auth.access-denied', [
                'user' => $user,
                'dashboardUrl' => $dashboardUrl,
            ]);
        }

        return view('auth.login');
    }

    /**
     * Login
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = ['login' => $request->login, 'password' => $request->password];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            $intended = session()->pull('url.intended', null);
            if ($intended) {
                $path = parse_url($intended, PHP_URL_PATH) ?? $intended;
                $allowedPrefix = match ($user->roles()->orderByDesc('level')->first()?->name) {
                    'super_admin', 'admin' => '/admin',
                    'teacher' => '/teacher',
                    'operator' => '/operator',
                    'accountant' => '/accountant',
                    'dean', 'vice_dean', 'department_head' => '/management',
                    'hr' => '/hr',
                    default => '/student',
                };

                if (!str_starts_with($path, $allowedPrefix)) {
                    $intended = null;
                }
            }

            return redirect()->to($intended ?: $this->redirectByRole($user));
        }

        return back()->withErrors(['login' => 'Логин ё парол нодуруст аст.'])->withInput($request->only('login'));
    }

    /**
     * Logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->hasSession()) {
            $request->session()->forget('url.intended');
        }

        $rememberName = Auth::guard()->getRecallerName();
        \Illuminate\Support\Facades\Cookie::queue(
            \Illuminate\Support\Facades\Cookie::forget($rememberName)
        );

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
            'accountant' => '/accountant/dashboard',
            'dean', 'vice_dean', 'department_head' => '/management/dashboard',
            'hr' => '/hr/dashboard',
            default => '/student/dashboard',
        };
    }
}
