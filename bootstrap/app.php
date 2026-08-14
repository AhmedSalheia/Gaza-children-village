<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\VerifyPortalSessionVersion;
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
        $middleware->alias([
            // Portal-aware Authenticate middleware: redirects unauthenticated
            // requests to the correct portal login page based on URL prefix.
            'auth' => Authenticate::class,

            // Session-version middleware: rejects sessions whose stored
            // auth_version no longer matches the account's current value,
            // enabling server-side session revocation per portal.
            'portal.version' => VerifyPortalSessionVersion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Return 401 for routes that cannot determine a login redirect (e.g. API
        // routes or unknown URL prefixes). Portal routes redirect to their own
        // login page via Authenticate::redirectTo() above.
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($e->redirectTo($request) === null) {
                return response('Unauthenticated.', 401);
            }
        });
    })->create();
