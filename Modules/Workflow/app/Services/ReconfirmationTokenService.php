<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Modules\Workflow\Contracts\ReconfirmationChallengeContract;
use Modules\Workflow\Contracts\SubjectContentResolverContract;
use Modules\Workflow\Exceptions\ElectronicApprovalException;
use Modules\Workflow\Models\ElectronicApprovalToken;
use Modules\Workflow\Models\ReconfirmationAttempt;

/**
 * Issues and consumes single-use, time-limited reconfirmation tokens.
 *
 * Security design:
 *
 *   Actor identity (type, account ID, portal):
 *     Derived from the portal session via ReconfirmationChallengeContract.
 *     Never accepted from caller-supplied parameters. The contract's concrete
 *     implementations (AdminReconfirmationChallenge, StaffReconfirmationChallenge)
 *     resolve actor identity via auth('admin')→user() / auth('staff')→user().
 *
 *   Credential verification:
 *     The service calls challenge->checkCredential() server-side. There is no
 *     passwordVerified=true bypass — the service always performs the check itself.
 *
 *   Rate limiting — concurrent-safe:
 *     Phase 1 (atomic, inside DB::transaction with per-actor lockForUpdate):
 *       (a) Upsert the actor's sentinel row in reconfirmation_actor_locks.
 *       (b) Lock that row so concurrent requests for the same actor queue behind it.
 *       (c) Count attempts in the rolling window.
 *       (d) If at or above MAX_ATTEMPTS_PER_WINDOW: throw — no attempt recorded.
 *       (e) Insert a provisional attempt row (succeeded=false); commit.
 *     Phase 2 (outside transaction — avoids holding a DB lock during bcrypt):
 *       Call challenge->checkCredential(credential).
 *     Phase 3 (outside transaction):
 *       If credential succeeds: mark the provisional row succeeded=true; issue token.
 *       If credential fails:   provisional row stays succeeded=false; throw.
 *     Both successful and failed attempts consume exactly one rate-limit slot.
 *
 *   Content hash:
 *     Must be a canonical SHA-256 hex string (exactly 64 lowercase hex chars).
 *
 *   TTL:
 *     Callers may request a shorter TTL; the service caps it at MAX_TTL_MINUTES (10).
 *     MAX_ATTEMPTS_PER_WINDOW and WINDOW_SECONDS are private constants — callers
 *     cannot weaken either control.
 */
final class ReconfirmationTokenService
{
    /** Default token lifetime (exposed so callers can display a countdown). */
    public const TOKEN_TTL_MINUTES = 5;

    /** Hard ceiling: no token may live longer than this. */
    private const MAX_TTL_MINUTES = 10;

    /** Maximum total attempts (success + failure) per actor per rolling window. */
    private const MAX_ATTEMPTS_PER_WINDOW = 5;

    /** Rolling window length in seconds. */
    private const WINDOW_SECONDS = 900; // 15 minutes

    /**
     * Verify the credential via the portal challenge and, on success, issue a token.
     *
     * @param  ReconfirmationChallengeContract  $challenge  Portal-bound verifier that derives
     *                                                      actor identity from the live session
     *                                                      and checks the credential server-side.
     * @param  string  $credential  Raw credential submitted by the user.
     * @param  SubjectContentResolverContract  $contentResolver  Domain-owned resolver that reads
     *                                                           the subject from the database and
     *                                                           returns its canonical SHA-256 hex
     *                                                           hash. The service calls this to bind
     *                                                           the current content server-side;
     *                                                           callers cannot supply their own hash.
     * @param  string  $approvalType  Stable approval code (e.g. 'sensitive_field_correction').
     * @param  string  $subjectType  Domain model class name. Token is subject-bound.
     * @param  int  $subjectId  Domain model primary key. Token is subject-bound.
     * @param  int  $ttlMinutes  Requested token lifetime; capped at MAX_TTL_MINUTES (10).
     *
     * @throws ElectronicApprovalException When rate-limited, credential fails, or inputs are invalid.
     */
    public function issue(
        ReconfirmationChallengeContract $challenge,
        string $credential,
        SubjectContentResolverContract $contentResolver,
        string $approvalType,
        string $subjectType,
        int $subjectId,
        int $ttlMinutes = self::TOKEN_TTL_MINUTES,
    ): ElectronicApprovalToken {
        if (empty(trim($approvalType))) {
            throw new ElectronicApprovalException('approvalType must not be empty.');
        }

        if (empty(trim($subjectType))) {
            throw new ElectronicApprovalException('subjectType must not be empty.');
        }

        // Compute canonical content hash server-side — the resolver reads the subject
        // from the database; callers cannot forge a hash string.
        $contentHash = $contentResolver->computeCanonicalHash($subjectType, $subjectId);

        // Validate the resolver's output. Implementations must return a canonical SHA-256 hex.
        if (! preg_match('/^[a-f0-9]{64}$/', $contentHash)) {
            throw new ElectronicApprovalException(
                'SubjectContentResolver returned an invalid hash: must be exactly 64 lowercase hexadecimal characters (SHA-256).'
            );
        }

        // Enforce hard TTL ceiling — callers cannot extend the short-lived policy.
        $ttlMinutes = min($ttlMinutes, self::MAX_TTL_MINUTES);
        $actorType = $challenge->actorType();
        $actorAccountId = $challenge->actorAccountId();
        $portal = $challenge->portal();

        // -----------------------------------------------------------------------
        // Phase 1 (ATOMIC): per-actor rate-limit check + provisional slot reservation.
        //
        // lockForUpdate on the actor's sentinel row serialises concurrent requests
        // from the same actor. Only one transaction holds the lock at a time;
        // others queue behind it. This prevents two concurrent requests from both
        // observing a count below MAX and both proceeding to issue tokens.
        // -----------------------------------------------------------------------
        $provisionalAttemptId = DB::transaction(function () use ($actorType, $actorAccountId, $portal): int {
            // Ensure the actor's sentinel row exists (upsert) then lock it.
            DB::table('reconfirmation_actor_locks')->upsert(
                ['actor_type' => $actorType, 'actor_account_id' => $actorAccountId],
                ['actor_type', 'actor_account_id'],
                ['actor_account_id'] // touch column to satisfy MySQL ON DUPLICATE KEY syntax
            );

            DB::table('reconfirmation_actor_locks')
                ->where('actor_type', $actorType)
                ->where('actor_account_id', $actorAccountId)
                ->lockForUpdate()
                ->first();

            // Count all attempts (success + failure) in the rolling window.
            $windowStart = now()->subSeconds(self::WINDOW_SECONDS);
            $count = ReconfirmationAttempt::where('actor_account_id', $actorAccountId)
                ->where('actor_type', $actorType)
                ->where('created_at', '>=', $windowStart)
                ->count();

            if ($count >= self::MAX_ATTEMPTS_PER_WINDOW) {
                throw new ElectronicApprovalException(
                    'Too many reconfirmation attempts. Please wait before trying again.'
                );
            }

            // Reserve a slot: insert a provisional "failed" attempt row.
            // If the credential check succeeds below, this row is updated to succeeded=true.
            // If it fails, the row stays as-is. Either way exactly one slot is consumed.
            $attempt = new ReconfirmationAttempt;
            $attempt->actor_type = $actorType;
            $attempt->actor_account_id = $actorAccountId;
            $attempt->portal = $portal;
            $attempt->succeeded = false;
            $attempt->save();

            return $attempt->id;
        });

        // -----------------------------------------------------------------------
        // Phase 2: Credential check OUTSIDE the DB transaction.
        // Avoids holding the actor lock for the full bcrypt verification duration.
        // -----------------------------------------------------------------------
        $succeeded = $challenge->checkCredential($credential);

        // -----------------------------------------------------------------------
        // Phase 3: Record the final outcome and, on success, issue the token.
        // -----------------------------------------------------------------------
        if (! $succeeded) {
            // Provisional row stays succeeded=false. No token issued.
            throw new ElectronicApprovalException(
                'Reconfirmation failed: incorrect password. Please try again.'
            );
        }

        // Mark the provisional slot as succeeded.
        DB::table('reconfirmation_attempts')
            ->where('id', $provisionalAttemptId)
            ->update(['succeeded' => true]);

        $token = new ElectronicApprovalToken;
        $token->id = ElectronicApprovalToken::generateId();
        $token->actor_type = $actorType;
        $token->actor_portal = $portal;
        $token->actor_account_id = $actorAccountId;
        $token->content_hash = $contentHash;
        $token->approval_type = $approvalType;
        $token->subject_type = $subjectType;
        $token->subject_id = $subjectId;
        $token->reconfirmation_method = 'password';
        $token->expires_at = now()->addMinutes($ttlMinutes);
        $token->save();

        return $token;
    }

    /**
     * Validate a token's bindings and mark it consumed inside an existing DB transaction.
     *
     * MUST be called within a DB::transaction(). Uses lockForUpdate so concurrent
     * consumption attempts are serialised.
     *
     * @throws ElectronicApprovalException
     */
    public function validateAndMarkConsumed(
        string $tokenId,
        string $actorType,
        int $actorAccountId,
        string $portal,
        string $contentHash,
        string $expectedApprovalType,
        string $expectedSubjectType,
        int $expectedSubjectId,
    ): ElectronicApprovalToken {
        $token = ElectronicApprovalToken::lockForUpdate()->find($tokenId);

        if ($token === null) {
            throw new ElectronicApprovalException('Reconfirmation token not found.');
        }

        if ($token->isConsumed()) {
            throw new ElectronicApprovalException(
                'Reconfirmation token has already been used. Please restart the approval process.'
            );
        }

        if ($token->isExpired()) {
            throw new ElectronicApprovalException(
                'Reconfirmation token has expired. Please restart the approval process.'
            );
        }

        // Actor binding — identity resolved from session at issuance, verified again at consumption.
        if ($token->actor_type !== $actorType
            || $token->actor_account_id !== $actorAccountId
            || $token->actor_portal !== $portal
        ) {
            throw new ElectronicApprovalException(
                'Reconfirmation token actor mismatch. Token is bound to a different account.'
            );
        }

        // Content hash binding — guards against content changes between load and submit.
        if (! hash_equals($token->content_hash, $contentHash)) {
            throw new ElectronicApprovalException(
                'Electronic approval rejected: subject content changed between review load and submission. '.
                'Please reload the review screen and try again.'
            );
        }

        // Approval type binding — prevents spending a token across different approval types.
        if ($token->approval_type !== $expectedApprovalType) {
            throw new ElectronicApprovalException(
                'Reconfirmation token is bound to a different approval type.'
            );
        }

        // Subject binding — prevents cross-subject reuse even when content hashes are identical.
        if ($token->subject_type !== $expectedSubjectType || $token->subject_id !== $expectedSubjectId) {
            throw new ElectronicApprovalException(
                'Reconfirmation token is bound to a different subject. '.
                'A token cannot be spent for a different subject than it was issued for.'
            );
        }

        // Mark consumed inside the caller's transaction.
        $token->consumed_at = now();
        $token->save();

        return $token;
    }

    /**
     * Standalone consumption — wraps in its own transaction.
     *
     * @throws ElectronicApprovalException
     */
    public function consume(
        string $tokenId,
        string $actorType,
        int $actorAccountId,
        string $portal,
        string $contentHash,
        string $expectedApprovalType,
        string $expectedSubjectType,
        int $expectedSubjectId,
    ): ElectronicApprovalToken {
        return DB::transaction(fn () => $this->validateAndMarkConsumed(
            tokenId: $tokenId,
            actorType: $actorType,
            actorAccountId: $actorAccountId,
            portal: $portal,
            contentHash: $contentHash,
            expectedApprovalType: $expectedApprovalType,
            expectedSubjectType: $expectedSubjectType,
            expectedSubjectId: $expectedSubjectId,
        ));
    }
}
