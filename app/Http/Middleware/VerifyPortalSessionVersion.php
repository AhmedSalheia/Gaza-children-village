<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the portal-specific session auth-version on every protected request.
 *
 * When RevokePortalAccountSessions increments an account's auth_version, all
 * existing sessions holding the old version are rejected here on their next
 * request. Only the affected portal guard is logged out — other portal guards
 * in the same browser session are completely unaffected.
 *
 * Session version lifecycle:
 * - Set by AuthenticatePortalAccount immediately after successful login.
 * - Compared here on every protected request.
 * - Cleared by LogoutPortalAccount on explicit logout.
 * - Stale (null) sessions from before F10 are accepted and backfilled once.
 *
 * Usage: add 'portal.version:<guard>' to the protected route middleware stack.
 * Example: Route::middleware(['auth:admin', 'portal.version:admin'])
 */
final class VerifyPortalSessionVersion
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $user = Auth::guard($guard)->user();

        // If the guard has no authenticated user, auth middleware handles it.
        if ($user === null) {
            return $next($request);
        }

        $sessionKey = "auth_version_{$guard}";
        $storedVersion = $request->session()->get($sessionKey);

        if ($storedVersion === null) {
            // Session predates auth-version tracking (e.g. migrated from F09).
            // Accept this request and backfill the current version so future
            // requests can detect revocation correctly.
            $request->session()->put($sessionKey, $user->auth_version);
        } elseif ((int) $storedVersion !== (int) $user->auth_version) {
            // Version mismatch — this session was revoked via RevokePortalAccountSessions.
            // Log out only this portal's guard; other portal sessions are unaffected.
            Auth::guard($guard)->logout();
            $request->session()->forget($sessionKey);
            $request->session()->regenerateToken();

            return redirect()->route("{$guard}.login");
        }

        return $next($request);
    }
}
