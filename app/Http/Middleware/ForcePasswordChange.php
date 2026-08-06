<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Агар must_change_password = true → ба саҳифаи иваз кардани парол redirect мекунад
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            // Иҷозат диҳед ки ба change-password ва logout дастрасӣ дошта бошад
            if (!$request->is('change-password*') && !$request->is('logout')) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
