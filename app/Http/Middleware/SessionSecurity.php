<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware барои амнияти сессия
 * - Назорати воридшавии ҳамзамон
 * - Тоза кардани сессияи кӯҳна
 * - Навсозии last_login_at
 */
class SessionSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Санҷиши ҳолати корбар
        if (!$user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Ҳисоби шумо ғайрифаъол карда шуд.');
        }

        // Навсозии фаъолияти охирин дар сессия
        session(['last_activity' => now()->timestamp]);

        return $next($request);
    }
}
