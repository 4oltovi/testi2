<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware барои санҷиши нақши корбар
 *
 * Истифода: Route::middleware('role:admin,dean')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Лутфан аввал ба система ворид шавед.');
        }

        if (!$user->isActive()) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Ҳисоби шумо ғайрифаъол аст. Бо администратор тамос гиред.');
        }

        // Super Admin ҳамеша дастрасӣ дорад
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Санҷиши нақшҳо
        if (!empty($roles) && !$user->hasAnyRole($roles)) {
            abort(403, 'Шумо ба ин бахш дастрасӣ надоред.');
        }

        return $next($request);
    }
}
