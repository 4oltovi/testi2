<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class SingleSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && Setting::get('single_session_enabled', true)) {
            $token = $request->session()->get('single_session_token');

            if ($user->session_token && $token !== $user->session_token) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')->with(
                    'error',
                    'Шумо аз система хориҷ шудед, зеро аз дигар дастгоҳ ворид шудед. Дар як вақт танҳо як сессия имконпазир аст!'
                );
            }
        }

        return $next($request);
    }
}
