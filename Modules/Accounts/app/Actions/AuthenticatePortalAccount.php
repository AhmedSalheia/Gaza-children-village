<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Data\LoginResult;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\AuthenticationEventType;
use Modules\Accounts\Enums\LoginFailureCategory;
use Modules\Accounts\Services\LoginIdentifierNormalizer;

/**
 * Core portal login action.
 *
 * Validates rate limits, checks credentials and lifecycle, and on success
 * logs the account in, stores the session auth-version, and regenerates the
 * session identifier (session-fixation protection).
 *
 * Security constraints:
 * - Only the PortalAuthConfig's guard is consulted — never a guard from request input.
 * - Credential failures and lifecycle failures produce identical public errors.
 * - Raw identifiers never appear in cache keys or event records.
 * - The password is never stored, logged, or forwarded after this action.
 * - Event recording failures are silently absorbed and never change the outcome.
 */
final class AuthenticatePortalAccount
{
    public function __construct(
        private readonly LoginIdentifierNormalizer $normalizer,
        private readonly BuildLoginThrottleKey $buildKey,
        private readonly RecordAuthenticationEvent $recordEvent,
        private readonly RateLimiter $limiter,
    ) {}

    public function __invoke(
        PortalAuthConfig $config,
        string $rawIdentifier,
        #[\SensitiveParameter] string $password,
        string $ip,
        Request $request,
    ): LoginResult {
        $normalizedId = $this->normalizer->normalize($rawIdentifier);
        $keys = ($this->buildKey)($config->portal, $normalizedId, $ip);

        $maxId = (int) config('portal-auth.throttle.max_identifier_attempts', 5);
        $maxIp = (int) config('portal-auth.throttle.max_ip_attempts', 30);
        $decay = (int) config('portal-auth.throttle.decay_seconds', 60);

        $ua = mb_substr((string) $request->userAgent(), 0, 200) ?: null;
        $correlationId = $request->header('X-Request-Id') ?: null;

        // ── 1. IP-level throttle check (broadest gate) ─────────────────────
        if ($this->limiter->tooManyAttempts($keys->ipKey, $maxIp)) {
            ($this->recordEvent)(
                portal: $config->portal,
                eventType: AuthenticationEventType::LoginThrottled,
                accountId: null,
                accountType: null,
                identifierFingerprint: $keys->identifierFingerprint,
                success: false,
                failureCategory: LoginFailureCategory::Throttled->value,
                correlationId: $correlationId,
                ipFingerprint: $keys->ipFingerprint,
                userAgentSummary: $ua,
            );

            return LoginResult::throttled($this->limiter->availableIn($keys->ipKey));
        }

        // ── 2. Per-identifier throttle check ───────────────────────────────
        if ($this->limiter->tooManyAttempts($keys->identifierKey, $maxId)) {
            ($this->recordEvent)(
                portal: $config->portal,
                eventType: AuthenticationEventType::LoginThrottled,
                accountId: null,
                accountType: null,
                identifierFingerprint: $keys->identifierFingerprint,
                success: false,
                failureCategory: LoginFailureCategory::Throttled->value,
                correlationId: $correlationId,
                ipFingerprint: $keys->ipFingerprint,
                userAgentSummary: $ua,
            );

            return LoginResult::throttled($this->limiter->availableIn($keys->identifierKey));
        }

        // ── 3. Retrieve account by identifier (no password check yet) ──────
        $provider = Auth::guard($config->portal)->getProvider();
        $user = $provider->retrieveByCredentials([$config->identifierField => $normalizedId]);

        // ── 4. Verify password ─────────────────────────────────────────────
        // Unknown identifier and wrong password produce the same public error.
        if ($user === null || ! Hash::check($password, $user->{$user->getAuthPasswordName()})) {
            $this->limiter->hit($keys->identifierKey, $decay);
            $this->limiter->hit($keys->ipKey, $decay);

            ($this->recordEvent)(
                portal: $config->portal,
                eventType: AuthenticationEventType::LoginFailed,
                accountId: null, // Do not confirm whether the account exists.
                accountType: null,
                identifierFingerprint: $keys->identifierFingerprint,
                success: false,
                failureCategory: LoginFailureCategory::BadCredentials->value,
                correlationId: $correlationId,
                ipFingerprint: $keys->ipFingerprint,
                userAgentSummary: $ua,
            );

            return LoginResult::failed();
        }

        // ── 5. Lifecycle check ─────────────────────────────────────────────
        // Pending, suspended, locked, and revoked accounts produce the same
        // public error as an invalid password. Internal events record the
        // opaque category for security analysis.
        if (! $user->status->canAuthenticate()) {
            $this->limiter->hit($keys->identifierKey, $decay);
            $this->limiter->hit($keys->ipKey, $decay);

            ($this->recordEvent)(
                portal: $config->portal,
                eventType: AuthenticationEventType::LoginFailed,
                accountId: $user->getKey(),            // Account known; record it for analysis.
                accountType: $config->accountModelClass,
                identifierFingerprint: $keys->identifierFingerprint,
                success: false,
                failureCategory: LoginFailureCategory::AccountNotActive->value,
                correlationId: $correlationId,
                ipFingerprint: $keys->ipFingerprint,
                userAgentSummary: $ua,
            );

            return LoginResult::failed();
        }

        // ── 6. Successful authentication ───────────────────────────────────
        Auth::guard($config->portal)->login($user);

        // Store the auth-version in the portal-specific session key so the
        // VerifyPortalSessionVersion middleware can detect revocation.
        $request->session()->put("auth_version_{$config->portal}", $user->auth_version);

        // Regenerate session ID to prevent session-fixation attacks.
        // Session data (including the newly stored user + version) is preserved.
        $request->session()->regenerate();

        // Clear only the identifier-specific counter. The IP counter persists
        // to resist distributed credential-stuffing attempts.
        $this->limiter->clear($keys->identifierKey);

        ($this->recordEvent)(
            portal: $config->portal,
            eventType: AuthenticationEventType::LoginSucceeded,
            accountId: $user->getKey(),
            accountType: $config->accountModelClass,
            identifierFingerprint: $keys->identifierFingerprint,
            success: true,
            failureCategory: null,
            correlationId: $correlationId,
            ipFingerprint: $keys->ipFingerprint,
            userAgentSummary: $ua,
        );

        return LoginResult::success();
    }
}
