<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\AuthenticationEventType;

/**
 * Revokes all active sessions for a single portal account.
 *
 * Increments the account's auth_version column inside a database transaction.
 * The VerifyPortalSessionVersion middleware detects the version mismatch on
 * the next protected request and logs the account out of only that portal —
 * other portal guards in the same browser are not affected.
 *
 * No HTTP context is required — this action is callable from management
 * commands, queued jobs, or administrative application services.
 *
 * Do not create management routes or UI for this action in F10.
 * Test it directly via the action service.
 */
final class RevokePortalAccountSessions
{
    public function __construct(
        private readonly RecordAuthenticationEvent $recordEvent,
    ) {}

    public function __invoke(
        Authenticatable $account,
        PortalAuthConfig $config,
    ): void {
        $accountId = $account->getKey();

        DB::transaction(function () use ($account, $accountId, $config): void {
            // Increment atomically. All sessions storing the old version are
            // now stale and will be rejected on the next protected request.
            /** @var Model $account */
            $account->increment('auth_version');

            ($this->recordEvent)(
                portal: $config->portal,
                eventType: AuthenticationEventType::SessionsRevoked,
                accountId: $accountId,
                accountType: $config->accountModelClass,
                identifierFingerprint: null,
                success: true,
                failureCategory: null,
                correlationId: null,
                ipFingerprint: null,
                userAgentSummary: null,
            );
        });
    }
}
