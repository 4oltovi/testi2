<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware барои санҷиши иҷозат
 *
 * Истифода: Route::middleware('permission:journal.grades')
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin ҳамеша дастрасӣ дорад
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Санҷиши иҷозат
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Шумо иҷозати анҷоми ин амалро надоред.');
    }
}
