<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
