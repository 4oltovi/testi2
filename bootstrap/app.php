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
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
