<?php

declare(strict_types=1);

namespace App\Http\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Workflow\Contracts\ReconfirmationChallengeContract;
use RuntimeException;

/**
 * Portal-bound reconfirmation challenge for the Staff guard.
 *
 * Derives actor identity from the currently authenticated staff session
 * (via `auth('staff')->user()`). Verifies the submitted credential against
 * the account's stored password hash using `Hash::check()`.
 *
 * Usage — instantiated by the Livewire approval component in the staff portal:
 *
 *   $challenge = new StaffReconfirmationChallenge();
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
final class StaffReconfirmationChallenge implements ReconfirmationChallengeContract
{
    public function actorType(): string
    {
        return 'staff';
    }

    public function actorAccountId(): int
    {
        $id = Auth::guard('staff')->id();

        if ($id === null) {
            throw new RuntimeException(
                'StaffReconfirmationChallenge: no authenticated staff session.'
            );
        }

        return (int) $id;
    }

    public function portal(): string
    {
        return 'staff';
    }

    /**
     * Verify the submitted credential against the currently authenticated staff account's
     * password hash. Uses Laravel's `Hash::check()` which honours the configured
     * password hasher (bcrypt/argon2).
     */
    public function checkCredential(string $credential): bool
    {
        $user = Auth::guard('staff')->user();

        if ($user === null) {
            return false;
        }

        return Hash::check($credential, $user->getAuthPassword());
    }
}
