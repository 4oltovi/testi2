<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware барои сабти амалҳои муҳим дар audit log
 */
class AuditMiddleware
{
    /**
     * Амалҳое, ки бояд сабт шаванд
     */
    private array $auditableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Танҳо амалҳои тағйирдиҳандаро сабт мекунем
        if (in_array($request->method(), $this->auditableMethods) && $request->user()) {
            $this->logAction($request, $response);
        }

        return $response;
    }

    private function logAction(Request $request, Response $response): void
    {
        // Агар response мувафақ бошад
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            try {
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => $this->getAction($request->method()),
                    'description' => $this->getDescription($request),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            } catch (\Throwable $e) {
                // Audit log-ро бе хато нигоҳ медорем
                report($e);
            }
        }
    }

    private function getAction(string $method): string
    {
        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'unknown',
        };
    }

    private function getDescription(Request $request): string
    {
        $route = $request->route();
        $routeName = $route?->getName() ?? $request->path();
        $method = $request->method();

        return "{$method} {$routeName}";
    }
}
