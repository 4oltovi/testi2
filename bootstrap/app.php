<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            require base_path('routes/admin.php');
            require base_path('routes/teacher.php');
            require base_path('routes/student.php');
            require base_path('routes/operator.php');
            require base_path('routes/management.php');
            require base_path('routes/accountant.php');
            require base_path('routes/hr.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'             => \App\Http\Middleware\RoleMiddleware::class,
        'permission'       => \App\Http\Middleware\PermissionMiddleware::class,
        'audit'            => \App\Http\Middleware\AuditMiddleware::class,
        'force.password'   => \App\Http\Middleware\ForcePasswordChange::class,
        'single.session'   => \App\Http\Middleware\SingleSession::class,
        'restrict.offline' => \App\Http\Middleware\RestrictOfflineAccess::class,
        'dean.readonly'    => \App\Http\Middleware\DeanReadOnly::class,
        'deny.hr.admin'   => \App\Http\Middleware\DenyHrAdminAccess::class,
        'track.session'    => \App\Http\Middleware\TrackSessionActivity::class,
    ]);

    $middleware->web(append: [
        \App\Http\Middleware\ForcePasswordChange::class,
        \App\Http\Middleware\SetCacheHeaders::class,
        \App\Http\Middleware\TrackSessionActivity::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage() ?? 'Дастрасӣ нест.'], 403);
            }

            $user = $request->user();
            if ($user) {
                return response()->view('auth.access-denied', [
                    'user' => $user,
                    'message' => $e->getMessage(),
                    'intended' => $request->path(),
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Лутфан аввал ба система ворид шавед.');
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Саҳифа кӯҳна шуд. Дубора кӯшиш кунед.',
                    'token' => csrf_token(),
                ], 419);
            }

            if ($request->is('POST') && ($request->is('logout') || $request->routeIs('logout'))) {
                if ($request->hasSession()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect('/login')->with('success', 'Шумо бо муваффақият баромад кардед.');
            }

            return redirect()->back()->with('csrf_error', 'Саҳифа кӯҳна шуд. Дубора кӯшиш кунед.')->withInput($request->except('password'));
        });
    })->create();
