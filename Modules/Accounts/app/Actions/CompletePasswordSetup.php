<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\AuthenticationEventType;
use Modules\Accounts\Enums\ChallengePurpose;
use Modules\Accounts\Enums\ChallengeValidationResult;
use SensitiveParameter;

/**
 * Complete a password setup or reset after a valid challenge is verified.
 *
 * Steps on success:
 * 1. Validate the submitted challenge token.
 * 2. Rotate the password using the secure hasher.
 * 3. Revoke all remaining open challenges for this account+portal.
 * 4. Revoke all existing sessions via the F10 auth-version mechanism.
 * 5. Record a password-change security event (no secrets in the payload).
 *
 * Returns a typed result; callers should NOT expose internal failure reasons
 * publicly — map all failures to the same generic "invalid or expired link" message.
 *
 * Privacy: the new password carries SensitiveParameter and is never logged.
 */
final class CompletePasswordSetup
{
    public function __construct(
        private readonly ValidateChallenge $validate,
        private readonly RevokeAccountChallenges $revokeChallenges,
        private readonly RevokePortalAccountSessions $revokeSessions,
        private readonly RecordAuthenticationEvent $recorder,
    ) {}

    public function __invoke(
        Authenticatable $account,
        PortalAuthConfig $config,
        ChallengePurpose $purpose,
        #[SensitiveParameter] string $plaintextToken,
        #[SensitiveParameter] string $newPassword,
    ): ChallengeValidationResult {
        $result = ($this->validate)($account, $config, $purpose, $plaintextToken);

        if (! $result->isValid()) {
            return $result;
        }

        // Rotate the password — never logged.
        $account->password = Hash::make($newPassword);
        $account->save();

        // Revoke all remaining open challenges for this account+portal.
        ($this->revokeChallenges)($account, $config->portal);

        // Revoke all existing sessions so that old sessions cannot be reused.
        ($this->revokeSessions)($account, $config);

        // Record the security event without any secret payload.
        $eventType = $purpose === ChallengePurpose::InitialPasswordSetup
            ? AuthenticationEventType::PasswordSetupCompleted
            : AuthenticationEventType::PasswordResetCompleted;

        ($this->recorder)(
            portal: $config->portal,
            eventType: $eventType,
            accountId: $account->getAuthIdentifier(),
            accountType: $account::class,
            identifierFingerprint: null,
            success: true,
        );

        return ChallengeValidationResult::Valid;
    }
}
