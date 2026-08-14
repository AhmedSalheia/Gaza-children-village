<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/admin.php',
            __DIR__.'/../routes/staff.php',
            __DIR__.'/../routes/guardian.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Replace the default Authenticate middleware with the portal-aware
        // version that returns null from redirectTo (handled below).
        $middleware->alias([
            'auth' => Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Return 401 for unauthenticated requests instead of redirecting to a
        // login route. No portal login routes exist in F09. F10 will update this
        // to redirect to the correct portal-specific login page.
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            return response('Unauthenticated.', 401);
        });
    })->create();
