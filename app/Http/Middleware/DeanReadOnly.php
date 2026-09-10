<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeanReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isManagement()) {
            return $next($request);
        }

        $method = $request->method();

        if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            abort(403, 'Шумо иҷозати тағйир додани маълумотро надоред. Ин кабинет танҳо хондан моҷуд аст.');
        }

        return $next($request);
    }
}
