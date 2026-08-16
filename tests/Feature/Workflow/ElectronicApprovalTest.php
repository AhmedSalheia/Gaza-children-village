<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Audit\Models\AuditEvent;
use Modules\Audit\Services\DatabaseAuditRecorder;
use Modules\Audit\Services\NullAuditRecorder;
use Modules\Workflow\Contracts\ReconfirmationChallengeContract;
use Modules\Workflow\Contracts\SubjectContentResolverContract;
use Modules\Workflow\Data\TransitionContext;
use Modules\Workflow\Database\Seeders\WorkflowDefinitionSeeder;
use Modules\Workflow\Exceptions\ElectronicApprovalException;
use Modules\Workflow\Models\ElectronicApproval;
use Modules\Workflow\Models\ElectronicApprovalToken;
use Modules\Workflow\Models\ReconfirmationAttempt;
use Modules\Workflow\Models\WorkflowDefinition;
use Modules\Workflow\Services\ElectronicApprovalService;
use Modules\Workflow\Services\ReconfirmationTokenService;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Test double: portal-bound challenge stub
// ---------------------------------------------------------------------------

/**
 * Stub implementation of ReconfirmationChallengeContract for tests.
 *
 * Represents a portal-guard-backed verifier where actor identity is derived
 * from the authenticated session. In production this is AdminReconfirmationChallenge
 * or StaffReconfirmationChallenge, both of which call auth('admin'/'staff')->user()
 * to resolve identity and Hash::check() for credential verification.
 */
final class StubReconfirmationChallenge implements ReconfirmationChallengeContract
{
    public function __construct(
        private readonly string $actorType,
        private readonly int $actorAccountId,
        private readonly string $portal,
        private readonly bool $credentialValid,
    ) {}

    public function actorType(): string
    {
        return $this->actorType;
    }

    public function actorAccountId(): int
    {
        return $this->actorAccountId;
    }

    public function portal(): string
    {
        return $this->portal;
    }

    public function checkCredential(string $credential): bool
    {
        return $this->credentialValid;
    }
}

// ---------------------------------------------------------------------------
// Test double: domain-owned subject content resolver stub
// ---------------------------------------------------------------------------

/**
 * Stub implementation of SubjectContentResolverContract for tests.
 *
 * In production this is a domain module class (e.g. CorrectionRequestContentResolver)
 * that reads the subject from the database and returns hash('sha256', canonicalContent).
 * The stub accepts a map of subjectType:subjectId → content string, so tests can
 * simulate both stable content (same hash at issuance and submission) and changed
 * content (different hash — stale-approval attack scenario).
 */
final class StubSubjectContentResolver implements SubjectContentResolverContract
{
    /** @param array<string, string> $contentMap  key format: "SubjectType:id" */
    public function __construct(private readonly array $contentMap = []) {}

    public function computeCanonicalHash(string $subjectType, int $subjectId): string
    {
        $key = "{$subjectType}:{$subjectId}";
        $content = $this->contentMap[$key] ?? 'canonical subject content';

        return hash('sha256', $content);
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeTokenService(): ReconfirmationTokenService
{
    return new ReconfirmationTokenService;
}

function makeApprovalService(?AuditRecorder $recorder = null): ElectronicApprovalService
{
    return new ElectronicApprovalService(
        auditRecorder: $recorder ?? app(AuditRecorder::class),
        tokenService: makeTokenService(),
    );
}

function adminContext(int $accountId = 1): TransitionContext
{
    return new TransitionContext(
        actorType: 'administrative',
        portal: 'admin',
        actorAccountId: $accountId,
        comment: 'Test approval',
    );
}

/** Build a resolver whose hash for (subjectType, subjectId) is derived from $content. */
function resolver(
    string $content = 'canonical subject content',
    string $subjectType = 'StudentCorrectionRequest',
    int $subjectId = 42,
): StubSubjectContentResolver {
    return new StubSubjectContentResolver(["{$subjectType}:{$subjectId}" => $content]);
}

/** Raw SHA-256 hex helper — used only for consume() which still accepts a hash directly. */
function contentHash(string $content = 'canonical subject content'): string
{
    return hash('sha256', $content);
}

/**
 * Build a challenge that will succeed or fail credential verification.
 */
function challenge(
    int $accountId = 1,
    bool $valid = true,
    string $actorType = 'administrative',
    string $portal = 'admin',
): StubReconfirmationChallenge {
    return new StubReconfirmationChallenge($actorType, $accountId, $portal, $valid);
}

/**
 * Issue a valid reconfirmation token for tests.
 */
function issueToken(
    string $content = 'canonical subject content',
    int $accountId = 1,
    string $approvalType = 'sensitive_field_correction',
    string $subjectType = 'StudentCorrectionRequest',
    int $subjectId = 42,
): ElectronicApprovalToken {
    return makeTokenService()->issue(
        challenge: challenge($accountId),
        credential: 'correct-password',
        contentResolver: resolver($content, $subjectType, $subjectId),
        approvalType: $approvalType,
        subjectType: $subjectType,
        subjectId: $subjectId,
    );
}

// ---------------------------------------------------------------------------
// ReconfirmationTokenService tests
// ---------------------------------------------------------------------------

describe('ReconfirmationTokenService', function (): void {

    beforeEach(function (): void {
        app()->bind(AuditRecorder::class, NullAuditRecorder::class);
    });

    // -----------------------------------------------------------------------
    // Successful issuance
    // -----------------------------------------------------------------------

    it('issues a valid token when the challenge succeeds', function (): void {
        $token = issueToken();

        expect($token->id)->toBeString()
            ->and(strlen($token->id))->toBe(36) // UUID
            ->and($token->reconfirmation_method)->toBe('password')
            ->and($token->subject_type)->toBe('StudentCorrectionRequest')
            ->and($token->subject_id)->toBe(42)
            ->and($token->approval_type)->toBe('sensitive_field_correction')
            ->and($token->consumed_at)->toBeNull()
            ->and($token->isValid())->toBeTrue();
    });

    it('content hash on the token is computed by the resolver (server-side), not supplied by the caller', function (): void {
        $token = issueToken('the exact content shown at review time');

        // The token stores the hash of the resolver's output — the caller never touched it.
        expect($token->content_hash)->toBe(contentHash('the exact content shown at review time'));
    });

    it('persists a succeeded attempt row on successful issuance', function (): void {
        issueToken();

        expect(ReconfirmationAttempt::where('succeeded', true)->count())->toBe(1);
    });

    // -----------------------------------------------------------------------
    // Credential verification enforced
    // -----------------------------------------------------------------------

    it('throws and records a failed attempt when the credential is wrong', function (): void {
        $svc = makeTokenService();

        expect(fn () => $svc->issue(
            challenge: challenge(valid: false),
            credential: 'wrong-password',
            contentResolver: resolver(),
            approvalType: 'test',
            subjectType: 'Foo',
            subjectId: 1,
        ))->toThrow(ElectronicApprovalException::class, 'incorrect password');

        // No token should have been issued
        expect(ElectronicApprovalToken::count())->toBe(0)
            // But the failed attempt IS persisted for rate-limit accounting
            ->and(ReconfirmationAttempt::where('succeeded', false)->count())->toBe(1);
    });

    it('cannot forge a token by bypassing the challenge — the service calls checkCredential() itself', function (): void {
        // Even if a caller constructs a challenge returning false, issue() must throw.
        // There is no passwordVerified=true bypass: the service always calls checkCredential().
        $svc = makeTokenService();
        $badChallenge = challenge(valid: false);

        expect(fn () => $svc->issue($badChallenge, 'any-string', resolver(), 'test', 'Foo', 1))
            ->toThrow(ElectronicApprovalException::class);

        expect(ElectronicApprovalToken::count())->toBe(0);
    });

    // -----------------------------------------------------------------------
    // Input validation
    // -----------------------------------------------------------------------

    it('throws when the resolver returns an invalid hash (not 64 lowercase hex chars)', function (): void {
        // Simulate a badly-implemented resolver that returns a non-canonical string.
        $badResolver = new class implements SubjectContentResolverContract
        {
            public function computeCanonicalHash(string $subjectType, int $subjectId): string
            {
                return 'not-a-sha256';
            }
        };

        expect(fn () => makeTokenService()->issue(
            challenge: challenge(),
            credential: 'pass',
            contentResolver: $badResolver,
            approvalType: 'test',
            subjectType: 'Foo',
            subjectId: 1,
        ))->toThrow(ElectronicApprovalException::class, 'invalid hash');
    });

    it('throws when the resolver returns uppercase hex (non-canonical SHA-256)', function (): void {
        $uppercaseResolver = new class implements SubjectContentResolverContract
        {
            public function computeCanonicalHash(string $subjectType, int $subjectId): string
            {
                return strtoupper(hash('sha256', 'test'));
            }
        };

        expect(fn () => makeTokenService()->issue(
            challenge: challenge(),
            credential: 'pass',
            contentResolver: $uppercaseResolver,
            approvalType: 'test',
            subjectType: 'Foo',
            subjectId: 1,
        ))->toThrow(ElectronicApprovalException::class, 'invalid hash');
    });

    it('throws when subjectType is blank', function (): void {
        expect(fn () => makeTokenService()->issue(
            challenge: challenge(),
            credential: 'pass',
            contentResolver: resolver(),
            approvalType: 'test',
            subjectType: '   ',
            subjectId: 1,
        ))->toThrow(ElectronicApprovalException::class, 'subjectType');
    });

    // -----------------------------------------------------------------------
    // Rate limiting — failed attempts count
    // -----------------------------------------------------------------------

    it('rate-limits after MAX_ATTEMPTS_PER_WINDOW total attempts including failed ones', function (): void {
        $svc = makeTokenService();

        // Exhaust limit with failed attempts (5 = MAX_ATTEMPTS_PER_WINDOW)
        for ($i = 0; $i < 5; $i++) {
            try {
                $svc->issue(challenge(accountId: 77, valid: false), 'wrong', resolver("content-{$i}", 'Foo', $i + 1), 'test', 'Foo', $i + 1);
            } catch (ElectronicApprovalException) {
                // expected — each failure records an attempt
            }
        }

        // Now even a correct credential should be rate-limited
        expect(fn () => $svc->issue(challenge(accountId: 77, valid: true), 'correct', resolver('ok'), 'test', 'Foo', 99))
            ->toThrow(ElectronicApprovalException::class, 'Too many reconfirmation attempts');

        expect(ReconfirmationAttempt::where('actor_account_id', 77)->count())->toBe(5);
    });

    it('rate-limit is atomic — sequential requests that would race serialize correctly via actor lock', function (): void {
        // Verify that the actor lock row is upserted and used as a per-actor serialisation point.
        // In a single-process test this validates the correctness of the locked count + reservation
        // approach: each of the N calls atomically reserves a slot, so exactly MAX calls succeed
        // and the (MAX+1)th fails regardless of ordering.
        $svc = makeTokenService();
        $successes = 0;
        $rateBlocked = 0;

        for ($i = 0; $i < 7; $i++) {
            try {
                $svc->issue(challenge(accountId: 55, valid: true), 'pass', resolver("s-{$i}", 'Foo', $i + 1), 'test', 'Foo', $i + 1);
                $successes++;
            } catch (ElectronicApprovalException $e) {
                if (str_contains($e->getMessage(), 'Too many')) {
                    $rateBlocked++;
                }
            }
        }

        // Exactly MAX_ATTEMPTS_PER_WINDOW (5) should succeed; the remaining 2 should be blocked
        expect($successes)->toBe(5)
            ->and($rateBlocked)->toBe(2)
            // 5 provisional rows were inserted (one per slot reserved before credential check)
            ->and(ReconfirmationAttempt::where('actor_account_id', 55)->count())->toBe(5)
            // Actor lock sentinel row must exist after first issue
            ->and(DB::table('reconfirmation_actor_locks')
                ->where('actor_account_id', 55)->count())->toBe(1);
    });

    it('rate limit is per actor — exhausting one actor does not block another', function (): void {
        $svc = makeTokenService();

        // Exhaust actor 77
        for ($i = 0; $i < 5; $i++) {
            try {
                $svc->issue(challenge(accountId: 77, valid: false), 'wrong', resolver("c-{$i}", 'Foo', $i + 1), 'test', 'Foo', $i + 1);
            } catch (ElectronicApprovalException) {
            }
        }

        // Actor 88 should still be able to issue
        $token = $svc->issue(challenge(accountId: 88), 'correct', resolver('ok'), 'test', 'Foo', 1);
        expect($token->isValid())->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // TTL hard ceiling
    // -----------------------------------------------------------------------

    it('caps TTL at MAX_TTL_MINUTES even if a larger value is passed', function (): void {
        $svc = makeTokenService();
        $token = $svc->issue(challenge(), 'correct', resolver(), 'test', 'Foo', 1, ttlMinutes: 999);

        // Token should expire within MAX_TTL_MINUTES (10), not 999 minutes
        expect($token->expires_at->diffInMinutes(now()))->toBeLessThanOrEqual(10);
    });

    // -----------------------------------------------------------------------
    // Token consumption
    // -----------------------------------------------------------------------

    it('consumes a valid token successfully', function (): void {
        $svc = makeTokenService();
        $token = issueToken('test content');

        $consumed = $svc->consume(
            tokenId: $token->id,
            actorType: 'administrative',
            actorAccountId: 1,
            portal: 'admin',
            contentHash: contentHash('test content'),
            expectedApprovalType: 'sensitive_field_correction',
            expectedSubjectType: 'StudentCorrectionRequest',
            expectedSubjectId: 42,
        );

        expect($consumed->consumed_at)->not->toBeNull()
            ->and($consumed->isConsumed())->toBeTrue();
    });

    it('throws when token is consumed a second time (single-use)', function (): void {
        $svc = makeTokenService();
        $token = issueToken();

        $svc->consume($token->id, 'administrative', 1, 'admin', contentHash(), 'sensitive_field_correction', 'StudentCorrectionRequest', 42);

        expect(fn () => $svc->consume($token->id, 'administrative', 1, 'admin', contentHash(), 'sensitive_field_correction', 'StudentCorrectionRequest', 42))
            ->toThrow(ElectronicApprovalException::class, 'already been used');
    });

    it('throws when content hash at submission differs from hash at issuance', function (): void {
        $svc = makeTokenService();
        $token = issueToken('original content');

        expect(fn () => $svc->consume(
            tokenId: $token->id,
            actorType: 'administrative',
            actorAccountId: 1,
            portal: 'admin',
            contentHash: contentHash('modified content'),
            expectedApprovalType: 'sensitive_field_correction',
            expectedSubjectType: 'StudentCorrectionRequest',
            expectedSubjectId: 42,
        ))->toThrow(ElectronicApprovalException::class, 'content changed');
    });

    it('throws when approval type does not match the token binding', function (): void {
        $svc = makeTokenService();
        $token = issueToken(approvalType: 'sensitive_field_correction');

        expect(fn () => $svc->consume(
            tokenId: $token->id,
            actorType: 'administrative',
            actorAccountId: 1,
            portal: 'admin',
            contentHash: contentHash(),
            expectedApprovalType: 'document_issuance',
            expectedSubjectType: 'StudentCorrectionRequest',
            expectedSubjectId: 42,
        ))->toThrow(ElectronicApprovalException::class, 'bound to a different approval type');
    });

    it('throws when subject ID does not match the token binding', function (): void {
        $svc = makeTokenService();
        $token = issueToken(subjectId: 42);

        expect(fn () => $svc->consume(
            tokenId: $token->id,
            actorType: 'administrative',
            actorAccountId: 1,
            portal: 'admin',
            contentHash: contentHash(),
            expectedApprovalType: 'sensitive_field_correction',
            expectedSubjectType: 'StudentCorrectionRequest',
            expectedSubjectId: 999,
        ))->toThrow(ElectronicApprovalException::class, 'bound to a different subject');
    });

    it('throws when actor account id does not match token', function (): void {
        $svc = makeTokenService();
        $token = issueToken(accountId: 5);

        expect(fn () => $svc->consume(
            tokenId: $token->id,
            actorType: 'administrative',
            actorAccountId: 99,
            portal: 'admin',
            contentHash: contentHash(),
            expectedApprovalType: 'sensitive_field_correction',
            expectedSubjectType: 'StudentCorrectionRequest',
            expectedSubjectId: 42,
        ))->toThrow(ElectronicApprovalException::class, 'actor mismatch');
    });

    it('throws when token not found', function (): void {
        expect(fn () => makeTokenService()->consume(
            tokenId: 'nonexistent-uuid',
            actorType: 'administrative',
            actorAccountId: 1,
            portal: 'admin',
            contentHash: contentHash(),
            expectedApprovalType: 'test',
            expectedSubjectType: 'Foo',
            expectedSubjectId: 1,
        ))->toThrow(ElectronicApprovalException::class, 'not found');
    });

});

// ---------------------------------------------------------------------------
// ElectronicApprovalService tests
// ---------------------------------------------------------------------------

describe('ElectronicApprovalService', function (): void {

    beforeEach(function (): void {
        app()->bind(AuditRecorder::class, NullAuditRecorder::class);
    });

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    it('records an approved decision after a valid token is consumed', function (): void {
        $svc = makeApprovalService();
        $token = issueToken();

        $approval = $svc->record(
            approvalType: 'sensitive_field_correction',
            decision: 'approved',
            subjectType: 'StudentCorrectionRequest',
            subjectId: 42,
            tokenId: $token->id,
            contentResolver: resolver(),
            context: adminContext(),
        );

        expect($approval->id)->toBeInt()
            ->and($approval->decision)->toBe('approved')
            ->and($approval->approval_type)->toBe('sensitive_field_correction')
            ->and($approval->is_revoked)->toBeFalse()
            ->and($approval->content_hash)->toBe(contentHash())
            ->and($approval->reconfirmation_method)->toBe('password');
    });

    it('content_hash on the approval comes from the token (server-side bound at issuance)', function (): void {
        $svc = makeApprovalService();
        $token = issueToken('content at load time');

        $approval = $svc->record(
            approvalType: 'sensitive_field_correction',
            decision: 'approved',
            subjectType: 'StudentCorrectionRequest',
            subjectId: 42,
            tokenId: $token->id,
            contentResolver: resolver('content at load time'),
            context: adminContext(),
        );

        expect($approval->content_hash)->toBe(contentHash('content at load time'));
    });

    it('records a rejected decision', function (): void {
        $svc = makeApprovalService();
        $token = issueToken(approvalType: 'document_issuance', subjectType: 'StudentDocumentRequest', subjectId: 7);

        $approval = $svc->record(
            approvalType: 'document_issuance',
            decision: 'rejected',
            subjectType: 'StudentDocumentRequest',
            subjectId: 7,
            tokenId: $token->id,
            contentResolver: resolver('canonical subject content', 'StudentDocumentRequest', 7),
            context: adminContext(),
        );

        expect($approval->decision)->toBe('rejected');
    });

    // -----------------------------------------------------------------------
    // Stale-approval protection (content changed between load and submit)
    // -----------------------------------------------------------------------

    it('rejects approval when the resolver detects the subject content changed after the token was issued', function (): void {
        // Scenario: approver loads review screen (token issued with hash of load-time content).
        // Before approver submits, the subject record is modified.
        // At submission, the resolver reads fresh content from DB — the hash differs.
        $svc = makeApprovalService();

        // Token issued when subject content was "Original Name"
        $token = issueToken('Original Name: Foo → Bar');

        // Simulate subject content changing after the token was issued:
        // The resolver now returns the hash of the *current* (modified) content.
        $staleResolver = resolver('Modified Name: Foo → Baz (changed after load)');

        expect(fn () => $svc->record(
            approvalType: 'sensitive_field_correction',
            decision: 'approved',
            subjectType: 'StudentCorrectionRequest',
            subjectId: 42,
            tokenId: $token->id,
            contentResolver: $staleResolver,
            context: adminContext(),
        ))->toThrow(ElectronicApprovalException::class, 'content changed');

        // No approval must have been persisted
        expect(ElectronicApproval::count())->toBe(0);

        // Token must NOT have been consumed — the approval failed before consumption
        $token->refresh();
        expect($token->consumed_at)->toBeNull();
    });

    // -----------------------------------------------------------------------
    // Rejection paths
    // -----------------------------------------------------------------------

    it('throws for an invalid decision value', function (): void {
        $svc = makeApprovalService();
        $token = issueToken();

        expect(fn () => $svc->record('sensitive_field_correction', 'maybe', 'StudentCorrectionRequest', 42, $token->id, resolver(), adminContext()))
            ->toThrow(ElectronicApprovalException::class);
    });

    it('throws when approval type does not match the token binding', function (): void {
        $svc = makeApprovalService();
        $token = issueToken(approvalType: 'sensitive_field_correction');

        expect(fn () => $svc->record(
            approvalType: 'document_issuance',
            decision: 'approved',
            subjectType: 'StudentCorrectionRequest',
            subjectId: 42,
            tokenId: $token->id,
            contentResolver: resolver(),
            context: adminContext(),
        ))->toThrow(ElectronicApprovalException::class, 'bound to a different approval type');
    });

    it('throws when subject ID does not match the token binding — prevents cross-subject reuse', function (): void {
        $svc = makeApprovalService();
        $token = issueToken(subjectId: 42);

        // resolver returns the correct hash for subject 42, but record() is called with subjectId 999
        expect(fn () => $svc->record(
            approvalType: 'sensitive_field_correction',
            decision: 'approved',
            subjectType: 'StudentCorrectionRequest',
            subjectId: 999,
            tokenId: $token->id,
            contentResolver: resolver('canonical subject content', 'StudentCorrectionRequest', 999),
            context: adminContext(),
        ))->toThrow(ElectronicApprovalException::class, 'bound to a different subject');
    });

    it('cannot reuse a token for a second approval', function (): void {
        $svc = makeApprovalService();
        $token = issueToken();

        $svc->record('sensitive_field_correction', 'approved', 'StudentCorrectionRequest', 42, $token->id, resolver(), adminContext());

        expect(fn () => $svc->record('sensitive_field_correction', 'approved', 'StudentCorrectionRequest', 42, $token->id, resolver(), adminContext()))
            ->toThrow(ElectronicApprovalException::class, 'already been used');
    });

    // -----------------------------------------------------------------------
    // Production-recorder integration: DatabaseAuditRecorder
    // -----------------------------------------------------------------------

    it('works end-to-end with DatabaseAuditRecorder (no forbidden keys in audit payload)', function (): void {
        $svc = makeApprovalService(recorder: app(DatabaseAuditRecorder::class));
        $token = issueToken();

        $approval = $svc->record(
            approvalType: 'sensitive_field_correction',
            decision: 'approved',
            subjectType: 'StudentCorrectionRequest',
            subjectId: 42,
            tokenId: $token->id,
            contentResolver: resolver(),
            context: adminContext(),
        );

        expect($approval->id)->toBeInt()
            ->and(ElectronicApproval::count())->toBe(1);

        $token->refresh();
        expect($token->consumed_at)->not->toBeNull();

        expect(DB::table('audit_events')
            ->where('action', 'electronic_approval.recorded')
            ->count()
        )->toBe(1);
    });

    it('rolls back approval and token consumption when the audit write fails', function (): void {
        $failingRecorder = new class implements AuditRecorder
        {
            public function record(AuditEventPayload $payload): AuditEvent
            {
                throw new InvalidArgumentException('Simulated audit failure');
            }
        };

        $svc = makeApprovalService(recorder: $failingRecorder);
        $token = issueToken();

        expect(fn () => $svc->record(
            approvalType: 'sensitive_field_correction',
            decision: 'approved',
            subjectType: 'StudentCorrectionRequest',
            subjectId: 42,
            tokenId: $token->id,
            contentResolver: resolver(),
            context: adminContext(),
        ))->toThrow(InvalidArgumentException::class, 'Simulated audit failure');

        expect(ElectronicApproval::count())->toBe(0);

        $token->refresh();
        expect($token->consumed_at)->toBeNull();
    });

    // -----------------------------------------------------------------------
    // Revocation
    // -----------------------------------------------------------------------

    it('revokes an existing approval atomically and creates a revocation record', function (): void {
        $svc = makeApprovalService();
        $token = issueToken();

        $original = $svc->record('sensitive_field_correction', 'approved', 'StudentCorrectionRequest', 42, $token->id, resolver(), adminContext());
        $revocation = $svc->revoke($original, adminContext(accountId: 2), 'Made in error');

        $original->refresh();

        expect($original->is_revoked)->toBeTrue()
            ->and($original->superseded_by_id)->toBe($revocation->id)
            ->and($revocation->decision)->toBe('revoked')
            ->and($revocation->is_revoked)->toBeFalse()
            ->and(ElectronicApproval::count())->toBe(2);
    });

    it('throws when revoking an already-revoked approval', function (): void {
        $svc = makeApprovalService();
        $token = issueToken();

        $approval = $svc->record('sensitive_field_correction', 'approved', 'StudentCorrectionRequest', 42, $token->id, resolver(), adminContext());
        $svc->revoke($approval, adminContext(), 'First revocation');
        $approval->refresh();

        expect(fn () => $svc->revoke($approval, adminContext(), 'Second revocation'))
            ->toThrow(ElectronicApprovalException::class, 'already revoked');
    });

    // -----------------------------------------------------------------------
    // Model scopes
    // -----------------------------------------------------------------------

    it('active scope excludes the revoked original but includes the revocation record', function (): void {
        $svc = makeApprovalService();

        $t1 = issueToken(accountId: 1, subjectId: 1);
        $svc->record('sensitive_field_correction', 'approved', 'StudentCorrectionRequest', 1, $t1->id, resolver('canonical subject content', 'StudentCorrectionRequest', 1), adminContext(1));

        $t2 = issueToken(accountId: 2, subjectId: 2);
        $toRevoke = $svc->record('sensitive_field_correction', 'approved', 'StudentCorrectionRequest', 2, $t2->id, resolver('canonical subject content', 'StudentCorrectionRequest', 2), adminContext(2));

        $svc->revoke($toRevoke, adminContext(3), 'Test revoke');

        expect(ElectronicApproval::count())->toBe(3)
            ->and(ElectronicApproval::active()->count())->toBe(2)
            ->and(ElectronicApproval::where('subject_id', 2)->where('is_revoked', true)->count())->toBe(1)
            ->and(ElectronicApproval::active()->where('subject_id', 1)->count())->toBe(1);
    });

});

// ---------------------------------------------------------------------------
// WorkflowDefinitionSeeder — student_document lifecycle consistency
// ---------------------------------------------------------------------------

describe('WorkflowDefinitionSeeder', function (): void {

    it('student_document machine does not list issued as a terminal state', function (): void {
        // Run seeder so the definition is present
        (new WorkflowDefinitionSeeder)->run();

        $def = WorkflowDefinition::where('type', 'student_document')->firstOrFail();

        expect($def->terminal_states)->not->toContain('issued')
            ->and($def->terminal_states)->toContain('superseded')
            ->and($def->terminal_states)->toContain('rejected')
            ->and($def->terminal_states)->toContain('cancelled');
    });

    it('issued → supersede → superseded transition is reachable (issued is not terminal)', function (): void {
        (new WorkflowDefinitionSeeder)->run();

        $def = WorkflowDefinition::where('type', 'student_document')->firstOrFail();
        $transitions = collect($def->transitions);

        // Verify the supersede transition exists from issued
        $supersede = $transitions->first(fn ($t) => $t['from'] === 'issued' && $t['action'] === 'supersede');
        expect($supersede)->not->toBeNull()
            ->and($supersede['to'])->toBe('superseded');

        // 'issued' must NOT be in terminal_states, otherwise WorkflowTransitionService would reject it
        expect($def->terminal_states)->not->toContain('issued');
    });

});
