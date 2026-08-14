<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\AuthenticationEventType;

/**
 * Portal-specific logout action.
 *
 * Logs out only the named portal's guard. Other portal guards in the same
 * browser session are completely unaffected. Rotates the CSRF token to
 * invalidate any in-flight form submissions.
 *
 * The session itself is NOT invalidated — session data belonging to other
 * guards is preserved. If you need to fully destroy the session (e.g. for
 * a "sign out of all portals" feature), that is out of scope for F10.
 *
 * Repeated logout calls are safe: if no session exists for the portal, the
 * guard's logout is a no-op and the CSRF token is still rotated.
 */
final class LogoutPortalAccount
{
    public function __construct(
        private readonly RecordAuthenticationEvent $recordEvent,
    ) {}

    public function __invoke(PortalAuthConfig $config, Request $request): void
    {
        $guard = Auth::guard($config->portal);
        $user = $guard->user();

        // Remove this portal's authentication from the session.
        // Other portal guards' session keys are untouched.
        $guard->logout();

        // Remove the portal-specific auth-version key so a future login
        // starts fresh rather than inheriting a stale version.
        $request->session()->forget("auth_version_{$config->portal}");

        // Rotate the CSRF token. The rest of the session is preserved.
        $request->session()->regenerateToken();

        // Record the event only when an authenticated account was present.
        if ($user !== null) {
            ($this->recordEvent)(
                portal: $config->portal,
                eventType: AuthenticationEventType::Logout,
                accountId: $user->getKey(),
                accountType: $config->accountModelClass,
                identifierFingerprint: null,
                success: true,
                failureCategory: null,
                correlationId: $request->header('X-Request-Id') ?: null,
                ipFingerprint: null,
                userAgentSummary: mb_substr((string) $request->userAgent(), 0, 200) ?: null,
            );
        }
    }
}
