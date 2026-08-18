<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class RestrictOfflineAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Танҳо дар режими оффлайн кор мекунад
        if (Setting::get('test_mode', 'online') === 'offline') {

            $ip = $request->ip();

            // IPv6-и локалӣ ба IPv4 табдил
            if (str_starts_with($ip, '::ffff:')) {
                $ip = substr($ip, 7);
            }

            // IP-ҳои локалӣ (шабакаи коллеҷ)
            $isLocal = $ip === '127.0.0.1'
                || $ip === '::1'
                || str_starts_with($ip, '192.168.')
                || str_starts_with($ip, '10.')
                || preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $ip) === 1;

            if (!$isLocal) {
                abort(403, '❌ Дар режими ОФФЛАЙН дастрасӣ танҳо аз шабакаи локалии коллеҷ имконпазир аст!');
            }
        }

        return $next($request);
    }
}
