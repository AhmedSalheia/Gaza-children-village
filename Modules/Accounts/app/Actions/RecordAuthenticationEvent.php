<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Modules\Accounts\Enums\AuthenticationEventType;
use Modules\Accounts\Models\AuthenticationEvent;

/**
 * Appends an immutable authentication security event to the event log.
 *
 * Privacy rules enforced here:
 * - Raw credentials, national IDs, session IDs, and CSRF tokens are never accepted.
 * - Identifiers and IP addresses are accepted only as pre-computed HMAC fingerprints.
 * - User-agent strings are truncated to 200 characters.
 * - Correlation ID comes from the X-Request-Id request header (or null).
 *
 * Failure contract:
 * - If the INSERT fails (e.g. DB unavailable), the exception is caught and logged.
 * - This action MUST NOT propagate exceptions to callers — a recording failure must
 *   never accidentally authenticate a failed request or deny a successful one.
 */
final class RecordAuthenticationEvent
{
    public function __invoke(
        string $portal,
        AuthenticationEventType $eventType,
        ?int $accountId,
        ?string $accountType,
        ?string $identifierFingerprint,
        bool $success,
        ?string $failureCategory = null,
        ?string $correlationId = null,
        ?string $ipFingerprint = null,
        ?string $userAgentSummary = null,
    ): void {
        try {
            AuthenticationEvent::create([
                'portal' => $portal,
                'event_type' => $eventType->value,
                'account_id' => $accountId,
                'account_type' => $accountType,
                'identifier_fingerprint' => $identifierFingerprint,
                'occurred_at' => now(),
                'success' => $success,
                'failure_category' => $failureCategory,
                'correlation_id' => $correlationId ?: null,
                'ip_fingerprint' => $ipFingerprint,
                'user_agent_summary' => $userAgentSummary,
            ]);
        } catch (\Throwable $e) {
            // Log but never propagate — recording failure must not change the auth outcome.
            logger()->warning('Authentication event recording failed', [
                'portal' => $portal,
                'event_type' => $eventType->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
