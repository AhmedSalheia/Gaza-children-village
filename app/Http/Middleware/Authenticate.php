<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Custom Authenticate middleware.
 *
 * Overrides the default redirect behavior to return null (401) instead of
 * redirecting to route('login'), which does not exist in F09.
 *
 * F10 will introduce portal-specific login routes and update this class to
 * redirect unauthenticated requests to the correct portal login page based
 * on the requested URL prefix.
 */
final class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // No login routes exist in F09. Return null to produce a 401 response
        // rather than a redirect to a non-existent login route.
        // F10 will add: return match(true) { ... } to redirect to portal login.
        return null;
    }
}
