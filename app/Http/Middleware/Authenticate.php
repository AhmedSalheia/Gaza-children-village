<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Portal-aware Authenticate middleware.
 *
 * Redirects unauthenticated requests to the appropriate portal login page
 * based on the URL prefix. The framework stores the originally-requested URL
 * in the session (url.intended) via redirect()->guest(), so after successful
 * login the user can be sent back to their intended destination.
 *
 * Non-browser routes (API, unknown prefixes) receive a null redirect, which
 * the exception handler in bootstrap/app.php converts to a 401 response.
 */
final class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        return match (true) {
            str_starts_with($request->path(), 'admin/') => route('admin.login'),
            str_starts_with($request->path(), 'staff/') => route('staff.login'),
            str_starts_with($request->path(), 'guardian/') => route('guardian.login'),
            default => null,
        };
    }
}
