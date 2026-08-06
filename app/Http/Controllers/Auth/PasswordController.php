<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Намоиши формаи тағйири парол
     */
    public function showChangeForm(): View
    {
        return view('auth.change-password');
    }

    /**
     * Тағйири парол
     */
    public function change(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Пароли ҳозира ҳатмӣ аст.',
            'password.required' => 'Пароли нав ҳатмӣ аст.',
            'password.min' => 'Пароли нав бояд ҳадди ақал 8 рамз дошта бошад.',
            'password.confirmed' => 'Тасдиқи парол мувофиқат намекунад.',
        ]);

        $user = $request->user();

        // Санҷиши пароли ҳозира
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Пароли ҳозира нодуруст аст.',
            ]);
        }

        // Навсозии парол
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLog::log('update', 'Парол тағйир дода шуд', 'App\Models\User', $user->id);

        return back()->with('success', 'Парол бомуваффақият тағйир дода шуд.');
    }
}
