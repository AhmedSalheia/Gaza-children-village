<?php

declare(strict_types=1);

namespace App\Http\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Workflow\Contracts\ReconfirmationChallengeContract;
use RuntimeException;

/**
 * Portal-bound reconfirmation challenge for the Admin guard.
 *
 * Derives actor identity from the currently authenticated admin session
 * (via `auth('admin')->user()`). Verifies the submitted credential against
 * the account's stored password hash using `Hash::check()`.
 *
 * Usage — instantiated by the Livewire approval component in the admin portal:
 *
 *   $challenge = new AdminReconfirmationChallenge();
 *   $token = $tokenService->issue(
 *       challenge:    $challenge,
 *       credential:   $this->password, // from the Livewire form field
 *       contentHash:  hash('sha256', $this->canonicalContent()),
 *       approvalType: 'sensitive_field_correction',
 *       subjectType:  'StudentCorrectionRequest',
 *       subjectId:    $this->correctionId,
 *   );
 *
 * The constructor does NOT accept account ID or actor type from the caller —
 * these are always resolved from the live guard session.
 */
final class AdminReconfirmationChallenge implements ReconfirmationChallengeContract
{
    public function actorType(): string
    {
        return 'administrative';
    }

    public function actorAccountId(): int
    {
        $id = Auth::guard('admin')->id();

        if ($id === null) {
            throw new RuntimeException(
                'AdminReconfirmationChallenge: no authenticated admin session.'
            );
        }

        return (int) $id;
    }

    public function portal(): string
    {
        return 'admin';
    }

    /**
     * Verify the submitted credential against the currently authenticated admin's
     * password hash. Uses Laravel's `Hash::check()` which honours the configured
     * password hasher (bcrypt/argon2).
     */
    public function checkCredential(string $credential): bool
    {
        $user = Auth::guard('admin')->user();

        if ($user === null) {
            return false;
        }

        return Hash::check($credential, $user->getAuthPassword());
    }
}
