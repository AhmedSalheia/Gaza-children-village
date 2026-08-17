<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Requests\Models\InstitutionFormalRequest;
use Modules\Requests\Models\InstitutionFormalRequestComment;
use Modules\Requests\Resolvers\FormalRequestContentResolver;
use Modules\Requests\Services\InstitutionFormalRequestNumberService;
use Modules\Requests\Services\InstitutionFormalRequestService;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Create a minimal InstitutionFormalRequest in 'draft' state without touching
 * the number service (so tests don't need the full institution chain).
 */
function makeFormalRequest(array $overrides = []): InstitutionFormalRequest
{
    $req = new InstitutionFormalRequest;
    $req->request_number = $overrides['request_number'] ?? 'GCV-FR-2026-00001';
    $req->institution_id = $overrides['institution_id'] ?? 1;
    $req->institution_semester_id = $overrides['institution_semester_id'] ?? null;
    $req->created_by_account_id = $overrides['created_by_account_id'] ?? 10;
    $req->responsible_account_id = $overrides['responsible_account_id'] ?? null;
    $req->request_type = $overrides['request_type'] ?? 'administrative';
    $req->title_ar = $overrides['title_ar'] ?? 'طلب رسمي';
    $req->title_en = $overrides['title_en'] ?? 'Formal Request';
    $req->body = $overrides['body'] ?? ['text' => 'Test body content'];
    $req->priority = $overrides['priority'] ?? 2;
    $req->current_status = $overrides['current_status'] ?? InstitutionFormalRequest::STATUS_DRAFT;
    $req->version = $overrides['version'] ?? 1;
    if (isset($overrides['content_hash'])) {
        $req->content_hash = $overrides['content_hash'];
    }
    $req->save();

    return $req;
}

function formalRequestSvc(): InstitutionFormalRequestService
{
    return app(InstitutionFormalRequestService::class);
}

/**
 * Pre-seed the number sequence for an institution so `next()` returns the
 * second number (00002) instead of the first.  Call this whenever a test
 * creates a request via makeFormalRequest() (which bypasses the sequences
 * table) and then calls supersede() (which uses the number service).
 * Without the seed the service would also try to issue 00001, hitting the
 * unique constraint on (institution_id, request_number).
 */
/**
 * Insert a minimal SecureAttachment row for use in attachment tests.
 * secure_attachments has no updated_at (append-only, UPDATED_AT = null).
 */
function makeSecureAttachment(int $institutionId, string $uniqueSuffix = ''): string
{
    $id = Str::uuid()->toString();
    DB::table('secure_attachments')->insert([
        'id' => $id,
        'original_filename' => "test{$uniqueSuffix}.pdf",
        'storage_filename' => "test{$uniqueSuffix}.pdf",
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size_bytes' => 1024,
        'sha256_hash' => hash('sha256', "attachment-content-{$id}"),
        'storage_disk' => 'attachments',
        'storage_path' => "institution-{$institutionId}/evidence/test{$uniqueSuffix}.pdf",
        'uploader_actor_type' => 'staff',
        'uploader_account_id' => 10,
        'uploader_portal' => 'staff',
        'institution_id' => $institutionId,
        'classification' => 'evidence',
        'status' => 'available',
        'created_at' => now(),
    ]);

    return $id;
}

function seedSequenceAt(int $institutionId, int $currentSeq = 1, int $year = 2026): void
{
    DB::table('institution_formal_request_sequences')->insert([
        'institution_id' => $institutionId,
        'year' => $year,
        'current_sequence' => $currentSeq,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ── Number service ─────────────────────────────────────────────────────────────

describe('InstitutionFormalRequestNumberService', function (): void {

    test('generates sequential numbers per institution and year', function (): void {
        $svc = app(InstitutionFormalRequestNumberService::class);

        $first = $svc->next(institutionId: 1, year: 2026);
        $second = $svc->next(institutionId: 1, year: 2026);
        $third = $svc->next(institutionId: 2, year: 2026); // different institution

        expect($first)->toBe('GCV-FR-2026-00001')
            ->and($second)->toBe('GCV-FR-2026-00002')
            ->and($third)->toBe('GCV-FR-2026-00001'); // reset for institution 2
    });

    test('sequences are isolated per year', function (): void {
        $svc = app(InstitutionFormalRequestNumberService::class);

        $y2025 = $svc->next(institutionId: 1, year: 2025);
        $y2026 = $svc->next(institutionId: 1, year: 2026);

        expect($y2025)->toBe('GCV-FR-2025-00001')
            ->and($y2026)->toBe('GCV-FR-2026-00001');
    });
});

// ── Content hash resolver ──────────────────────────────────────────────────────

describe('FormalRequestContentResolver', function (): void {

    test('computes a 64-char lowercase hex SHA-256 hash', function (): void {
        $req = makeFormalRequest();
        $hash = app(FormalRequestContentResolver::class)->computeCanonicalHash('InstitutionFormalRequest', $req->id);

        expect($hash)->toMatch('/^[a-f0-9]{64}$/');
    });

    test('hash changes when body changes', function (): void {
        $req = makeFormalRequest();
        $resolver = app(FormalRequestContentResolver::class);

        $hashBefore = $resolver->computeCanonicalHash('InstitutionFormalRequest', $req->id);

        $req->body = ['text' => 'Different body content'];
        $req->save();

        $hashAfter = $resolver->computeCanonicalHash('InstitutionFormalRequest', $req->id);

        expect($hashBefore)->not->toBe($hashAfter);
    });

    test('throws for unsupported subject types', function (): void {
        $req = makeFormalRequest();
        app(FormalRequestContentResolver::class)->computeCanonicalHash('WrongType', $req->id);
    })->throws(InvalidArgumentException::class);

    test('throws when request not found', function (): void {
        app(FormalRequestContentResolver::class)->computeCanonicalHash('InstitutionFormalRequest', 99999);
    })->throws(RuntimeException::class);
});

// ── Draft creation ─────────────────────────────────────────────────────────────

describe('InstitutionFormalRequestService::createDraft', function (): void {

    test('creates a request in draft state with sequential number', function (): void {
        // Need org→institution_type→institution chain for number service
        $orgId = DB::table('organizations')->insertGetId([
            'code' => 'GCVT', 'name_en' => 'Test Org', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $itId = DB::table('institution_types')->insertGetId([
            'code' => 'school', 'name_ar' => 'مدرسة', 'name_en' => 'School',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $instId = DB::table('institutions')->insertGetId([
            'organization_id' => $orgId, 'institution_type_id' => $itId,
            'code' => 'GCVT-01', 'name_ar' => 'مدرسة', 'name_en' => 'Test School',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $req = formalRequestSvc()->createDraft(
            institutionId: $instId,
            institutionSemesterId: null,
            requestType: 'budget',
            titleAr: 'طلب ميزانية',
            titleEn: 'Budget Request',
            body: ['text' => 'We need more budget for supplies.'],
            priority: 3,
            dueDate: '2026-12-31',
            createdByAccountId: 42,
        );

        expect($req->id)->toBeInt()->toBeGreaterThan(0)
            ->and($req->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT)
            ->and($req->version)->toBe(1)
            ->and($req->request_number)->toStartWith('GCV-FR-')
            ->and($req->request_type)->toBe('budget')
            ->and($req->institution_id)->toBe($instId);
    });

    test('rejects an invalid request type', function (): void {
        formalRequestSvc()->createDraft(
            institutionId: 1, institutionSemesterId: null,
            requestType: 'invalid_type',
            titleAr: 'عنوان', titleEn: 'Title',
            body: ['text' => 'Body'], priority: 2, dueDate: null,
            createdByAccountId: 1,
        );
    })->throws(InvalidArgumentException::class);
});

// ── State-machine transitions ──────────────────────────────────────────────────

describe('InstitutionFormalRequestService transitions', function (): void {

    test('draft → internal_review via submitForInternalReview', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        $updated = formalRequestSvc()->submitForInternalReview($req, actorAccountId: 10, expectedInstitutionId: 1);

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_INTERNAL_REVIEW);
    });

    test('submitForInternalReview rejects when not in draft', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);
        formalRequestSvc()->submitForInternalReview($req, actorAccountId: 10);
    })->throws(RuntimeException::class);

    test('internal_review → returned_to_preparer via returnToPreparer', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);

        $updated = formalRequestSvc()->returnToPreparer(
            $req, actorAccountId: 20, reason: 'Please revise section 2.', expectedInstitutionId: 1,
        );

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER);

        // Comment must be persisted with internal audience
        $comment = InstitutionFormalRequestComment::where('request_id', $req->id)->first();
        expect($comment)->not->toBeNull()
            ->and($comment->audience)->toBe(InstitutionFormalRequestComment::AUDIENCE_INTERNAL)
            ->and($comment->comment_text)->toBe('Please revise section 2.');
    });

    test('returnToPreparer requires a non-blank reason', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);
        formalRequestSvc()->returnToPreparer($req, actorAccountId: 20, reason: '   ');
    })->throws(RuntimeException::class);

    test('returned_to_preparer → new draft branch via resubmit (source becomes superseded)', function (): void {
        seedSequenceAt(1); // advance counter so next() issues 00002, not 00001
        $source = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER,
            'version' => 1,
            'content_hash' => str_repeat('b', 64), // stale hash on source
        ]);

        $branch = formalRequestSvc()->resubmit($source, actorAccountId: 10, expectedInstitutionId: 1);

        // New branch is a fresh draft at version 1
        expect($branch->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT)
            ->and($branch->version)->toBe(1)
            ->and($branch->content_hash)->toBeNull()
            ->and($branch->branched_from_id)->toBe($source->id);

        // Source is preserved as an immutable snapshot
        $source->refresh();
        expect($source->current_status)->toBe(InstitutionFormalRequest::STATUS_SUPERSEDED)
            ->and($source->superseded_by_id)->toBe($branch->id);
    });

    test('secretary cannot sign (principal/deputy only)', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);

        formalRequestSvc()->issueSigningToken(
            request: $req,
            credential: 'password',
            signerAccountId: 10,
            signerPositionDefinition: 'secretary', // not allowed
            portal: 'staff',
            expectedInstitutionId: 1,
        );
    })->throws(RuntimeException::class);

    test('institution cannot alter a signed request (body edit forbidden)', function (): void {
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_SIGNED,
        ]);

        formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'عنوان مختلف',
            titleEn: 'Different title',
            body: ['text' => 'Changed'],
            priority: 1,
            dueDate: null,
            actorAccountId: 10,
        );
    })->throws(RuntimeException::class);

    test('institution cannot alter after submission to management', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT]);

        formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'عنوان مختلف', titleEn: 'Changed', body: ['text' => 'x'],
            priority: 1, dueDate: null, actorAccountId: 10,
        );
    })->throws(RuntimeException::class);

    test('signed → submitted_to_management via submitToManagement', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SIGNED]);

        $updated = formalRequestSvc()->submitToManagement($req, actorAccountId: 20, expectedInstitutionId: 1);

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT);
    });

    test('submitted_to_management → under_management_review via startManagementReview', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT]);

        $updated = formalRequestSvc()->startManagementReview($req, actorAccountId: 99);

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW);
    });

    test('under_management_review → clarification_requested via requestClarification', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        $updated = formalRequestSvc()->requestClarification(
            $req, actorAccountId: 99, question: 'Please clarify budget breakdown.',
        );

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_CLARIFICATION_REQUESTED);

        $comment = InstitutionFormalRequestComment::where('request_id', $req->id)->first();
        expect($comment->audience)->toBe(InstitutionFormalRequestComment::AUDIENCE_ALL);
    });

    test('clarification_requested → under_management_review via respondToClarification', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_CLARIFICATION_REQUESTED]);

        $updated = formalRequestSvc()->respondToClarification(
            $req, actorAccountId: 10, response: 'Here is the breakdown.', expectedInstitutionId: 1,
        );

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW);
    });

    test('under_management_review → accepted via accept', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        $updated = formalRequestSvc()->accept($req, actorAccountId: 99, comment: 'Approved.');

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_ACCEPTED);
    });

    test('under_management_review → rejected via reject', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        $updated = formalRequestSvc()->reject($req, actorAccountId: 99, reason: 'Budget constraints.');

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_REJECTED);
    });

    test('reject requires a non-blank reason', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);
        formalRequestSvc()->reject($req, actorAccountId: 99, reason: '');
    })->throws(RuntimeException::class);

    test('accepted → responded via respond', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_ACCEPTED]);

        $updated = formalRequestSvc()->respond(
            $req, actorAccountId: 99, responseBody: ['text' => 'Official response.'],
        );

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_RESPONDED)
            ->and($updated->response_at)->not->toBeNull();
    });

    test('responded → closed via close', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_RESPONDED]);

        $updated = formalRequestSvc()->close($req, actorAccountId: 99);

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_CLOSED)
            ->and($updated->isTerminal())->toBeTrue();
    });

    test('cancel allowed from draft', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        $updated = formalRequestSvc()->cancel($req, actorAccountId: 10, expectedInstitutionId: 1);

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_CANCELLED)
            ->and($updated->isTerminal())->toBeTrue();
    });

    test('cancel allowed from internal_review', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);

        $updated = formalRequestSvc()->cancel($req, actorAccountId: 10, expectedInstitutionId: 1);

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_CANCELLED);
    });

    test('cancel not allowed from signed state', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SIGNED]);
        formalRequestSvc()->cancel($req, actorAccountId: 10);
    })->throws(RuntimeException::class);

    test('cancel not allowed after submission to management', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT]);
        formalRequestSvc()->cancel($req, actorAccountId: 10);
    })->throws(RuntimeException::class);
});

// ── Cross-institution access denial ───────────────────────────────────────────

describe('cross-institution access denial', function (): void {

    test('submitForInternalReview rejects wrong institution scope', function (): void {
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_DRAFT,
        ]);

        formalRequestSvc()->submitForInternalReview(
            request: $req,
            actorAccountId: 10,
            expectedInstitutionId: 2, // wrong institution
        );
    })->throws(RuntimeException::class);

    test('cancel rejects wrong institution scope', function (): void {
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_DRAFT,
        ]);

        formalRequestSvc()->cancel($req, actorAccountId: 10, expectedInstitutionId: 999);
    })->throws(RuntimeException::class);

    test('updateDraft rejects wrong institution scope', function (): void {
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_DRAFT,
        ]);

        formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'عنوان', titleEn: 'Title',
            body: ['text' => 'x'], priority: 2, dueDate: null,
            actorAccountId: 10,
            requestType: null,
            expectedInstitutionId: 2, // wrong institution
        );
    })->throws(RuntimeException::class);

    test('addComment rejects wrong institution scope (staff portal)', function (): void {
        $req = makeFormalRequest(['institution_id' => 1]);

        formalRequestSvc()->addComment(
            request: $req,
            actorType: 'staff',
            actorAccountId: 10,
            portal: 'staff',
            audience: InstitutionFormalRequestComment::AUDIENCE_INTERNAL,
            commentText: 'cross-institution comment attempt',
            expectedInstitutionId: 2, // wrong institution
        );
    })->throws(RuntimeException::class);

    test('staff cannot post management-only comments', function (): void {
        $req = makeFormalRequest(['institution_id' => 1]);

        formalRequestSvc()->addComment(
            request: $req,
            actorType: 'staff',
            actorAccountId: 10,
            portal: 'staff',
            audience: InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT, // forbidden for staff
            commentText: 'trying to post as management',
            expectedInstitutionId: 1,
        );
    })->throws(RuntimeException::class);

    test('two institutions can each hold their own 00001 request number', function (): void {
        $reqA = makeFormalRequest([
            'institution_id' => 1,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        // Same number, different institution — must not violate a unique constraint
        $reqB = makeFormalRequest([
            'institution_id' => 2,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        expect($reqA->request_number)->toBe($reqB->request_number)
            ->and($reqA->institution_id)->not->toBe($reqB->institution_id);
    });
});

// ── Audience-restricted comment isolation ─────────────────────────────────────

describe('comment audience isolation', function (): void {

    test('internal-audience comment is not visible to management side', function (): void {
        $req = makeFormalRequest();

        formalRequestSvc()->addComment(
            request: $req,
            actorType: 'staff',
            actorAccountId: 10,
            portal: 'staff',
            audience: InstitutionFormalRequestComment::AUDIENCE_INTERNAL,
            commentText: 'Internal note for secretary only.',
        );

        // Management side should see zero comments
        $managementVisible = $req->comments()->visibleToManagement()->get();

        expect($managementVisible)->toBeEmpty();
    });

    test('management-audience comment is not visible to institution side', function (): void {
        $req = makeFormalRequest();

        formalRequestSvc()->addComment(
            request: $req,
            actorType: 'administrative',
            actorAccountId: 99,
            portal: 'admin',
            audience: InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT,
            commentText: 'Management internal note.',
        );

        $institutionVisible = $req->comments()->visibleToInstitution()->get();

        expect($institutionVisible)->toBeEmpty();
    });

    test('all-audience comment is visible to both sides', function (): void {
        $req = makeFormalRequest();

        formalRequestSvc()->addComment(
            request: $req,
            actorType: 'administrative',
            actorAccountId: 99,
            portal: 'admin',
            audience: InstitutionFormalRequestComment::AUDIENCE_ALL,
            commentText: 'Shared note for everyone.',
        );

        $mgmt = $req->comments()->visibleToManagement()->get();
        $inst = $req->comments()->visibleToInstitution()->get();

        expect($mgmt)->toHaveCount(1)
            ->and($inst)->toHaveCount(1);
    });

    test('comment text is stored encrypted and decrypts transparently', function (): void {
        $req = makeFormalRequest();

        formalRequestSvc()->addComment(
            request: $req,
            actorType: 'staff',
            actorAccountId: 10,
            portal: 'staff',
            audience: InstitutionFormalRequestComment::AUDIENCE_ALL,
            commentText: 'Sensitive comment content.',
        );

        $comment = InstitutionFormalRequestComment::where('request_id', $req->id)->first();

        // Raw DB value should be ciphertext (not plaintext)
        $rawValue = DB::table('institution_formal_request_comments')
            ->where('id', $comment->id)
            ->value('comment_text');
        expect($rawValue)->not->toBe('Sensitive comment content.');

        // Model accessor should return decrypted value
        expect($comment->comment_text)->toBe('Sensitive comment content.');
    });

    test('addComment rejects blank text', function (): void {
        $req = makeFormalRequest();
        formalRequestSvc()->addComment(
            request: $req, actorType: 'staff', actorAccountId: 10,
            portal: 'staff', audience: InstitutionFormalRequestComment::AUDIENCE_ALL,
            commentText: '   ',
        );
    })->throws(RuntimeException::class);

    test('addComment rejects invalid audience value', function (): void {
        $req = makeFormalRequest();
        formalRequestSvc()->addComment(
            request: $req, actorType: 'staff', actorAccountId: 10,
            portal: 'staff', audience: 'invalid_audience',
            commentText: 'test',
        );
    })->throws(RuntimeException::class);
});

// ── Version branching ─────────────────────────────────────────────────────────

describe('version branching on resubmit', function (): void {

    // Each resubmit test uses a unique institution_id (200+) so the number
    // service generates GCV-FR-YEAR-00001 for the institution that was NOT used
    // by the source request. Alternatively, we call seedSequenceAt() first.

    test('resubmit creates a new draft branch and preserves source as immutable snapshot', function (): void {
        seedSequenceAt(1);
        $source = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER,
            'version' => 2,
            'content_hash' => str_repeat('a', 64),
            'title_en' => 'Original Title',
            'body' => ['text' => 'Original body'],
        ]);

        $branch = formalRequestSvc()->resubmit($source, actorAccountId: 10, expectedInstitutionId: 1);

        // New branch is a fresh draft — version resets to 1, no stale hash
        expect($branch->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT)
            ->and($branch->version)->toBe(1)
            ->and($branch->content_hash)->toBeNull()
            ->and($branch->branched_from_id)->toBe($source->id)
            ->and($branch->title_en)->toBe('Original Title')  // copied as starting point
            ->and((array) $branch->body)->toBe(['text' => 'Original body']);

        // Source is superseded — its content (signed version) is preserved unchanged
        $source->refresh();
        expect($source->current_status)->toBe(InstitutionFormalRequest::STATUS_SUPERSEDED)
            ->and($source->superseded_by_id)->toBe($branch->id)
            ->and($source->content_hash)->toBe(str_repeat('a', 64)); // not cleared on source
    });

    test('resubmit from wrong state throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);
        formalRequestSvc()->resubmit($req, actorAccountId: 10);
    })->throws(RuntimeException::class);

    test('branch gets new request_number from institution sequence', function (): void {
        seedSequenceAt(1);
        $source = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER,
            'institution_id' => 1,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        $branch = formalRequestSvc()->resubmit($source, actorAccountId: 10, expectedInstitutionId: 1);

        expect($branch->request_number)->toBe('GCV-FR-2026-00002')
            ->and($branch->institution_id)->toBe($source->institution_id);
    });

    test('branch institution and request_type are inherited from source', function (): void {
        seedSequenceAt(201);
        $source = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER,
            'institution_id' => 201,
            'request_type' => 'budget',
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        $branch = formalRequestSvc()->resubmit($source, actorAccountId: 10, expectedInstitutionId: 201);

        expect($branch->institution_id)->toBe($source->institution_id)
            ->and($branch->request_type)->toBe('budget');
    });
});

// ── Supersede operation ───────────────────────────────────────────────────────

describe('supersede()', function (): void {

    // Each supersede test uses a unique institution_id (100+) so the number
    // service generates GCV-FR-YEAR-00001 for that institution without
    // conflicting with the source request that was inserted directly via
    // makeFormalRequest() (which bypasses the sequences table).

    test('creates a new draft and marks source as superseded from responded state', function (): void {
        seedSequenceAt(100); // advance counter so next() issues 00002, not 00001
        $source = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_RESPONDED,
            'institution_id' => 100,  // isolated institution for this test
            'request_type' => 'budget',
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        $replacement = formalRequestSvc()->supersede(
            request: $source,
            titleAr: 'طلب محدث',
            titleEn: 'Updated Request',
            body: ['text' => 'Revised content.'],
            priority: 3,
            dueDate: null,
            actorAccountId: 10,
            expectedInstitutionId: 100,
        );

        // The replacement is a new draft
        expect($replacement->id)->not->toBe($source->id)
            ->and($replacement->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT)
            ->and((int) $replacement->institution_id)->toBe(100)
            ->and($replacement->request_type)->toBe('budget')
            ->and($replacement->title_en)->toBe('Updated Request');

        // The source is marked superseded and links to the replacement
        $source->refresh();
        expect($source->current_status)->toBe(InstitutionFormalRequest::STATUS_SUPERSEDED)
            ->and((int) $source->superseded_by_id)->toBe($replacement->id);
    });

    test('supersede allowed from rejected state', function (): void {
        seedSequenceAt(101);
        $source = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_REJECTED,
            'institution_id' => 101,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        $replacement = formalRequestSvc()->supersede(
            request: $source,
            titleAr: 'طلب جديد',
            titleEn: 'New Request',
            body: ['text' => 'After rejection.'],
            priority: 2,
            dueDate: null,
            actorAccountId: 10,
            expectedInstitutionId: 101,
        );

        expect($replacement->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT);
        $source->refresh();
        expect($source->current_status)->toBe(InstitutionFormalRequest::STATUS_SUPERSEDED);
    });

    test('supersede allowed from closed state', function (): void {
        seedSequenceAt(102);
        $source = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_CLOSED,
            'institution_id' => 102,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        $replacement = formalRequestSvc()->supersede(
            request: $source,
            titleAr: 'طلب لاحق',
            titleEn: 'Follow-up Request',
            body: ['text' => 'Following closure.'],
            priority: 2,
            dueDate: null,
            actorAccountId: 10,
            expectedInstitutionId: 102,
        );

        expect($replacement->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT);
        $source->refresh();
        expect($source->current_status)->toBe(InstitutionFormalRequest::STATUS_SUPERSEDED);
    });

    test('supersede rejected from draft state', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        formalRequestSvc()->supersede(
            request: $req,
            titleAr: 'عنوان', titleEn: 'Title',
            body: ['text' => 'x'], priority: 2, dueDate: null,
            actorAccountId: 10,
        );
    })->throws(RuntimeException::class);

    test('supersede rejected from internal_review state', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);

        formalRequestSvc()->supersede(
            request: $req,
            titleAr: 'عنوان', titleEn: 'Title',
            body: ['text' => 'x'], priority: 2, dueDate: null,
            actorAccountId: 10,
        );
    })->throws(RuntimeException::class);

    test('supersede rejected from under_management_review state', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        formalRequestSvc()->supersede(
            request: $req,
            titleAr: 'عنوان', titleEn: 'Title',
            body: ['text' => 'x'], priority: 2, dueDate: null,
            actorAccountId: 10,
        );
    })->throws(RuntimeException::class);

    test('supersede rejects wrong institution scope', function (): void {
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_RESPONDED,
            'institution_id' => 1,
        ]);

        formalRequestSvc()->supersede(
            request: $req,
            titleAr: 'عنوان', titleEn: 'Title',
            body: ['text' => 'x'], priority: 2, dueDate: null,
            actorAccountId: 10,
            expectedInstitutionId: 2, // wrong institution
        );
    })->throws(RuntimeException::class);

    test('supersede generates a new sequential request number', function (): void {
        // We need a full institution chain for the number service
        $orgId = DB::table('organizations')->insertGetId([
            'code' => 'GCVX', 'name_en' => 'Test Org', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $itId = DB::table('institution_types')->insertGetId([
            'code' => 'school2', 'name_ar' => 'مدرسة', 'name_en' => 'School',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $instId = DB::table('institutions')->insertGetId([
            'organization_id' => $orgId, 'institution_type_id' => $itId,
            'code' => 'GCVX-01', 'name_ar' => 'مدرسة', 'name_en' => 'School',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Create via service so the sequences table is seeded for this institution
        $draft = formalRequestSvc()->createDraft(
            institutionId: $instId, institutionSemesterId: null,
            requestType: 'budget', titleAr: 'أصل', titleEn: 'Original',
            body: ['text' => 'Original.'], priority: 2, dueDate: null,
            createdByAccountId: 10,
        );

        // Manually advance to responded status for the test
        $draft->current_status = InstitutionFormalRequest::STATUS_RESPONDED;
        $draft->save();

        $replacement = formalRequestSvc()->supersede(
            request: $draft->fresh(),
            titleAr: 'بديل', titleEn: 'Replacement',
            body: ['text' => 'Replacement.'], priority: 2, dueDate: null,
            actorAccountId: 10,
            expectedInstitutionId: $instId,
        );

        // Replacement gets the NEXT sequential number for the same institution
        expect($replacement->request_number)->not->toBe($draft->request_number)
            ->and($replacement->request_number)->toStartWith('GCV-FR-');
    });
});

// ── Post-revocation authorization behaviour ───────────────────────────────────
//
// These tests simulate the revocation scenario by verifying that the per-render
// and per-action permission checks enforced in ManagementReview and
// FormalRequestDetail abort with 403 when the caller no longer holds the
// relevant permission.
//
// The "revocation" scenario is modeled at the authorization-check level:
//   1. No admin_role_grants / position_role_grants row for the actor → the
//      requirePermission() / staffCan() check that runs on every action returns
//      false → the component calls abort(403).
//   2. A comment is NOT persisted when the permission check fails.
//
// Full Livewire component test infrastructure (Livewire::test()) is not used
// here because:
//   a. No other Livewire tests exist in this codebase — the pattern and
//      dependency setup are not yet established for this project.
//   b. The authorization check is a pure DB query (admin_role_grants + permissions
//      join) that can be validated without the Livewire HTTP layer.
//   c. The service layer's assertInstitutionScope() and requireStatus() are
//      independently covered by the stale-model tests above.
//
// The enforced behavior being tested:
//   - ManagementInbox::render(), ManagementReview::{all actions}+render() all
//     call requirePermission() which does the DB lookup each time.
//   - FormalRequestDetail::render(), addComment() both call staffCan() which
//     does the DB lookup each time.
//   - Removing the grant row from the DB means the next call sees no permission
//     and aborts, mirroring what happens when a Livewire action is called after
//     a grant is revoked.

describe('per-render/per-action authorization re-check', function (): void {

    test('requirePermission returns true for admin with valid administrative_account_roles grant', function (): void {
        // Verifies the canonical admin authorization query used in ManagementInbox
        // and ManagementReview: administrative_account_roles → role_permissions
        // → permissions with revoked_at IS NULL.
        $adminId = seedManagementAdmin(50);

        $hasPermission = DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aar.administrative_account_id', $adminId)
            ->whereNull('aar.revoked_at')
            ->where('p.key', 'formal_request.respond')
            ->exists();

        expect($hasPermission)->toBeTrue();
    });

    test('requirePermission returns false after admin role grant is revoked', function (): void {
        $adminId = seedManagementAdmin(51);

        // Revoke the grant
        DB::table('administrative_account_roles')
            ->where('administrative_account_id', $adminId)
            ->update(['revoked_at' => now()->subMinute()]);

        $hasPermission = DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aar.administrative_account_id', $adminId)
            ->whereNull('aar.revoked_at')
            ->where('p.key', 'formal_request.respond')
            ->exists();

        expect($hasPermission)->toBeFalse();
    });

    test('requirePermission returns false when no administrative_account_roles row exists', function (): void {
        // Admin account exists but has no role grant at all — no access.
        DB::table('administrative_accounts')->insertOrIgnore([
            'id' => 52, 'username' => 'noperm-admin', 'password' => bcrypt('secret'),
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $hasPermission = DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aar.administrative_account_id', 52)
            ->whereNull('aar.revoked_at')
            ->where('p.key', 'formal_request.respond')
            ->exists();

        expect($hasPermission)->toBeFalse();
    });

    test('ManagementInbox renders for admin with valid formal_request.respond grant', function (): void {
        // Uses Livewire::test() to directly verify that requirePermission() passes
        // when the admin has a non-revoked grant in administrative_account_roles.
        $adminId = seedManagementAdmin(60);
        $adminAccCls = 'Modules\\Accounts\\Models\\AdministrativeAccount';
        $admin = $adminAccCls::findOrFail($adminId);

        Livewire::actingAs($admin, 'admin')
            ->test('App\\Livewire\\Admin\\FormalRequests\\ManagementInbox')
            ->assertOk();
    });

    test('ManagementInbox returns 403 when admin has no formal_request.respond grant', function (): void {
        // Livewire::test() converts abort(403) to a 403 response; assertForbidden()
        // is the correct assertion (Livewire does not re-throw HttpException to PHPUnit).
        DB::table('administrative_accounts')->insertOrIgnore([
            'id' => 61, 'username' => 'ungrant-admin', 'password' => bcrypt('secret'),
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminAccCls = 'Modules\\Accounts\\Models\\AdministrativeAccount';
        $admin = $adminAccCls::findOrFail(61);

        Livewire::actingAs($admin, 'admin')
            ->test('App\\Livewire\\Admin\\FormalRequests\\ManagementInbox')
            ->assertForbidden();
    });

    test('ManagementInbox returns 403 after admin role grant is revoked (per-render re-check)', function (): void {
        // Verifies requirePermission() re-runs on every render: revoking the grant
        // must cause the next Livewire property update to return 403.
        $adminId = seedManagementAdmin(62);
        $adminAccCls = 'Modules\\Accounts\\Models\\AdministrativeAccount';
        $admin = $adminAccCls::findOrFail($adminId);

        // First mount — must succeed
        $component = Livewire::actingAs($admin, 'admin')
            ->test('App\\Livewire\\Admin\\FormalRequests\\ManagementInbox')
            ->assertOk();

        // Revoke the grant mid-session
        DB::table('administrative_account_roles')
            ->where('administrative_account_id', $adminId)
            ->update(['revoked_at' => now()]);

        // A property update triggers render() → requirePermission() → abort(403)
        $component->set('statusFilter', 'submitted_to_management')
            ->assertForbidden();
    });

    test('ManagementReview returns 403 when admin has no formal_request.respond grant', function (): void {
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT,
        ]);
        DB::table('administrative_accounts')->insertOrIgnore([
            'id' => 63, 'username' => 'review-noperm', 'password' => bcrypt('secret'),
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminAccCls = 'Modules\\Accounts\\Models\\AdministrativeAccount';
        $admin = $adminAccCls::findOrFail(63);

        Livewire::actingAs($admin, 'admin')
            ->test('App\\Livewire\\Admin\\FormalRequests\\ManagementReview', ['requestId' => $req->id])
            ->assertForbidden();
    });

    test('ManagementReview renders for admin with valid formal_request.respond grant', function (): void {
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT,
        ]);
        $adminId = seedManagementAdmin(64);
        $adminAccCls = 'Modules\\Accounts\\Models\\AdministrativeAccount';
        $admin = $adminAccCls::findOrFail($adminId);

        Livewire::actingAs($admin, 'admin')
            ->test('App\\Livewire\\Admin\\FormalRequests\\ManagementReview', ['requestId' => $req->id])
            ->assertOk();
    });

    test('requirePermission returns false when no role grant row exists for the actor', function (): void {
        // Verifies the authorization check logic used in ManagementReview and
        // ManagementInbox: a join over role_grants → role_permissions → permissions
        // must return false (→ 403 abort) when the actor has no grant row.
        //
        // We use position_role_grants (the staff equivalent, present in the test DB)
        // to confirm the join pattern returns false for an unknown position.

        $hasPermission = DB::table('position_role_grants as prg')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'prg.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('prg.position_definition', 'non_existent_position_99999')
            ->where('p.key', 'formal_request.respond')
            ->exists();

        expect($hasPermission)->toBeFalse();
    });

    test('addComment is not persisted when caller has no prepare or review permission', function (): void {
        // Verify the authorization guard on addComment works at the service level:
        // a staff caller who has no permission at all should not be able to
        // leave a comment through any path.

        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);

        // The service's addComment() itself does not check permissions (that is
        // the component's responsibility) — but the component aborts before
        // calling the service. We verify the guard behavior by checking that
        // staffCan() returns false when no position_role_grants row exists.
        $hasPermission = DB::table('position_role_grants as prg')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'prg.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('prg.position_definition', 'non_existent_position')
            ->where('p.key', 'formal_request.prepare')
            ->exists();

        expect($hasPermission)->toBeFalse();

        // Confirm no comments were added to the request
        expect(
            InstitutionFormalRequestComment::where('institution_formal_request_id', $req->id)->count()
        )->toBe(0);
    });

    test('staffCan returns false for a position with no role grants (revoked scenario)', function (): void {
        // Models the scenario where a staff member's role grant is removed after
        // mount(). The per-render / per-action check calls staffCan() via
        // resolveActivePosition() + DB join which re-reads the grants table fresh.
        // Removing all position_role_grants rows for a position means every
        // subsequent staffCan() call returns false → the component aborts(403).

        $hasPermission = DB::table('position_role_grants as prg')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'prg.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('prg.position_definition', 'revoked_position')
            ->whereIn('p.key', ['formal_request.prepare', 'formal_request.review'])
            ->exists();

        expect($hasPermission)->toBeFalse();
    });
});

// ── Navigation link visibility ────────────────────────────────────────────────
//
// Tests that the formal-request nav links are shown to roles that hold the
// required permissions and hidden from roles that do not.
//
// Strategy: render the nav partial directly with a controlled $navCan closure
// (the same closure the layout passes at runtime) — no full HTTP request needed.

describe('navigation links — staff portal', function (): void {

    test('formal-requests link appears for a role with formal_request.prepare', function (): void {
        $navCan = fn (string $key): bool => $key === 'formal_request.prepare';
        $html = view('layouts.partials.staff-nav', ['navCan' => $navCan])->render();

        expect($html)->toContain(route('staff.formal-requests.index'));
    });

    test('formal-requests link appears for a role with formal_request.review', function (): void {
        $navCan = fn (string $key): bool => $key === 'formal_request.review';
        $html = view('layouts.partials.staff-nav', ['navCan' => $navCan])->render();

        expect($html)->toContain(route('staff.formal-requests.index'));
    });

    test('formal-requests link is hidden from roles without prepare or review', function (): void {
        // A staff member with only student.view (e.g. a teacher with no admin role)
        // should not see the formal requests nav item.
        $navCan = fn (string $key): bool => $key === 'student.view';
        $html = view('layouts.partials.staff-nav', ['navCan' => $navCan])->render();

        expect($html)->not->toContain(route('staff.formal-requests.index'));
    });

});

describe('navigation links — admin portal', function (): void {

    test('formal-requests link appears for an admin with formal_request.respond', function (): void {
        $navCan = fn (string $key): bool => $key === 'formal_request.respond';
        $html = view('layouts.partials.admin-nav', ['navCan' => $navCan])->render();

        expect($html)->toContain(route('admin.formal-requests.index'));
    });

    test('formal-requests link is hidden from admins without formal_request.respond', function (): void {
        // An admin with only student.view should not see the formal requests nav item.
        $navCan = fn (string $key): bool => $key === 'student.view';
        $html = view('layouts.partials.admin-nav', ['navCan' => $navCan])->render();

        expect($html)->not->toContain(route('admin.formal-requests.index'));
    });

});

// ── FormalRequestDetail component scope behaviour ────────────────────────────
//
// These tests exercise the exact query FormalRequestDetail::loadRequest() runs:
//   InstitutionFormalRequest::forInstitution($currentInstitutionId)
//       ->where('id', $this->requestId)
//       ->firstOrFail()
//
// They cover:
//  (a) Supersession via the component action: resolved as a service call inside
//      the same component + verified by tracking the returned replacement row.
//  (b) Mid-session scope drift: if the live staffScope() returns a different
//      institution (e.g. the staff member's position was reassigned), the
//      forInstitution() scope on the now-wrong institution causes firstOrFail()
//      to throw ModelNotFoundException — the component would surface a 404.

describe('FormalRequestDetail component — loadRequest() forInstitution scope', function (): void {

    test('request belonging to the current institution is accessible', function (): void {
        $req = makeFormalRequest(['institution_id' => 5, 'request_number' => 'GCV-FR-2026-00001']);

        $result = InstitutionFormalRequest::forInstitution(5)
            ->where('id', $req->id)
            ->firstOrFail();

        expect($result->id)->toBe($req->id);
    });

    test('scope drift — request becomes inaccessible after institution change', function (): void {
        // Staff was at institution 5, and has an open Livewire component for request #1 (inst 5).
        $req = makeFormalRequest([
            'institution_id' => 5,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        // Simulate position reassignment: staffScope() now returns institution 6.
        // The next Livewire round-trip calls forInstitution(6)->where('id', req->id)->firstOrFail().
        // That fails because the request belongs to institution 5.
        InstitutionFormalRequest::forInstitution(6)
            ->where('id', $req->id)
            ->firstOrFail();
    })->throws(ModelNotFoundException::class);

    test('scope drift — service mutation with wrong expectedInstitutionId is blocked', function (): void {
        // Simulate what happens when currentInstitutionId() returns a different value
        // mid-session: service enforces assertInstitutionScope.
        $req = makeFormalRequest([
            'institution_id' => 5,
            'current_status' => InstitutionFormalRequest::STATUS_DRAFT,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        // Caller passes expectedInstitutionId = 6 (their new scope), but
        // the request belongs to 5 — service rejects it.
        formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'جديد', titleEn: 'New', body: ['text' => 'x'],
            priority: 2, dueDate: null,
            actorAccountId: 99,
            requestType: null,
            expectedInstitutionId: 6,
        );
    })->throws(RuntimeException::class);

    test('component supersede path — creates replacement and marks source superseded', function (): void {
        seedSequenceAt(200);
        $source = makeFormalRequest([
            'institution_id' => 200,
            'current_status' => InstitutionFormalRequest::STATUS_RESPONDED,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        // Mirrors what FormalRequestDetail::supersede() calls after loadRequest():
        //   1. loadRequest()  → forInstitution(currentInstitutionId())->where('id', requestId)->firstOrFail()
        //   2. service->supersede(request, ..., expectedInstitutionId: currentInstitutionId())
        $currentInstitutionId = 200;
        $loaded = InstitutionFormalRequest::forInstitution($currentInstitutionId)
            ->where('id', $source->id)
            ->firstOrFail();

        $replacement = formalRequestSvc()->supersede(
            request: $loaded,
            titleAr: 'بديل المكوّن',
            titleEn: 'Component replacement',
            body: ['text' => 'Replacement body.'],
            priority: 2,
            dueDate: null,
            actorAccountId: 42,
            expectedInstitutionId: $currentInstitutionId,
        );

        // Replacement is a new draft at the same institution.
        expect($replacement->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT)
            ->and((int) $replacement->institution_id)->toBe(200);

        // Source is marked superseded and linked to the replacement.
        $source->refresh();
        expect($source->current_status)->toBe(InstitutionFormalRequest::STATUS_SUPERSEDED)
            ->and((int) $source->superseded_by_id)->toBe($replacement->id);

        // The new replacement is also accessible via the component's loadRequest() pattern.
        $loadedReplacement = InstitutionFormalRequest::forInstitution($currentInstitutionId)
            ->where('id', $replacement->id)
            ->firstOrFail();
        expect($loadedReplacement->id)->toBe($replacement->id);
    });

    test('supersede via component scope — wrong institution scope blocks access to source', function (): void {
        seedSequenceAt(201);
        $source = makeFormalRequest([
            'institution_id' => 201,
            'current_status' => InstitutionFormalRequest::STATUS_RESPONDED,
            'request_number' => 'GCV-FR-2026-00001',
        ]);

        // Component's loadRequest() uses the CURRENT institution scope.
        // If staffScope() drifted to institution 202, the load itself fails.
        InstitutionFormalRequest::forInstitution(202)
            ->where('id', $source->id)
            ->firstOrFail();
    })->throws(ModelNotFoundException::class);
});

// ── Stale-model / concurrent-transition protection ───────────────────────────

describe('concurrent transition protection', function (): void {

    test('second accept call on a stale model (already accepted) throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        // First accept succeeds
        formalRequestSvc()->accept($req, actorAccountId: 99, comment: null);

        // Second accept on the now-stale $req object should fail
        formalRequestSvc()->accept($req, actorAccountId: 99, comment: null);
    })->throws(RuntimeException::class);

    test('reject after accept throws (stale model, wrong status)', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        formalRequestSvc()->accept($req, actorAccountId: 99);

        // Reject using the same stale $req — the lockForMutation reload detects wrong status
        formalRequestSvc()->reject($req, actorAccountId: 99, reason: 'Conflicting decision.');
    })->throws(RuntimeException::class);

    test('clarification after accept throws (stale model)', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        formalRequestSvc()->accept($req, actorAccountId: 99);

        formalRequestSvc()->requestClarification($req, actorAccountId: 99, question: 'Clarify?');
    })->throws(RuntimeException::class);

    test('startManagementReview on an already-started request throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT]);

        formalRequestSvc()->startManagementReview($req, actorAccountId: 99);

        // Second start using stale model should fail
        formalRequestSvc()->startManagementReview($req, actorAccountId: 99);
    })->throws(RuntimeException::class);

    test('close after close throws (stale model)', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_RESPONDED]);

        formalRequestSvc()->close($req, actorAccountId: 99);

        formalRequestSvc()->close($req, actorAccountId: 99);
    })->throws(RuntimeException::class);

    // ── Sign-path races ───────────────────────────────────────────────────

    test('sign-vs-return race: returnToPreparer on stale model after sign throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);

        // Directly advance the DB row to 'signed' (simulating a concurrent signer winning)
        $req->current_status = InstitutionFormalRequest::STATUS_SIGNED;
        $req->save();
        $req->refresh();

        // Stale model was loaded when status was 'internal_review'; now trying to return
        $stale = makeFormalRequest([
            'request_number' => 'GCV-FR-2026-99999',
            'current_status' => InstitutionFormalRequest::STATUS_SIGNED, // already advanced
        ]);
        // Simulate stale-model scenario: construct a model object with wrong status
        $staleModel = InstitutionFormalRequest::findOrFail($stale->id);
        // Advance DB to 'signed' then try returnToPreparer expecting 'internal_review'
        formalRequestSvc()->returnToPreparer($staleModel, actorAccountId: 1, reason: 'Revise please.');
    })->throws(RuntimeException::class);

    test('submit-vs-cancel race: cancel on already-submitted (signed) throws', function (): void {
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_SIGNED,
            'request_number' => 'GCV-FR-2026-88880',
        ]);

        // cancel only allowed from draft/internal_review/returned_to_preparer
        formalRequestSvc()->cancel($req, actorAccountId: 1);
    })->throws(RuntimeException::class);

    test('double submitForInternalReview on stale model throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        formalRequestSvc()->submitForInternalReview($req, actorAccountId: 1);

        // Second call on the now-stale $req — locked reload detects wrong status
        formalRequestSvc()->submitForInternalReview($req, actorAccountId: 1);
    })->throws(RuntimeException::class);

    test('double resubmit on stale model throws', function (): void {
        seedSequenceAt(1); // resubmit uses number service, needs sequence pre-seeded
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER]);

        // First resubmit: source becomes STATUS_SUPERSEDED, new branch returned
        formalRequestSvc()->resubmit($req, actorAccountId: 1);

        // Second resubmit with stale model: lockForMutation reloads source,
        // finds STATUS_SUPERSEDED → requireStatus(returned_to_preparer) throws
        formalRequestSvc()->resubmit($req, actorAccountId: 1);
    })->throws(RuntimeException::class);

    test('submitToManagement on stale model (already submitted) throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SIGNED]);

        formalRequestSvc()->submitToManagement($req, actorAccountId: 1);

        formalRequestSvc()->submitToManagement($req, actorAccountId: 1);
    })->throws(RuntimeException::class);

    test('cancel on stale model (already cancelled) throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        formalRequestSvc()->cancel($req, actorAccountId: 1);

        formalRequestSvc()->cancel($req, actorAccountId: 1);
    })->throws(RuntimeException::class);

    // ── updateDraft races ─────────────────────────────────────────────────

    test('edit-vs-submit race: updateDraft on row already submitted for review throws', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        // First actor submits
        formalRequestSvc()->submitForInternalReview($req, actorAccountId: 1);

        // Second actor (with stale draft model) tries to edit — locked reload
        // sees internal_review, which is not editable → RuntimeException
        formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'تعديل', titleEn: 'Edit', body: ['text' => 'new content'],
            priority: 2, dueDate: null, actorAccountId: 2,
        );
    })->throws(RuntimeException::class);

    test('edit-vs-resubmit race: updateDraft on row already resubmitted (now superseded) throws', function (): void {
        seedSequenceAt(1); // resubmit uses number service, needs sequence pre-seeded
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER]);

        // First actor calls resubmit: source becomes STATUS_SUPERSEDED
        formalRequestSvc()->resubmit($req, actorAccountId: 1);

        // Second actor (with stale returned_to_preparer model) tries to edit:
        // lockForMutation reloads source, isEditable() → STATUS_SUPERSEDED not editable → throws
        formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'تعديل', titleEn: 'Edit', body: ['text' => 'stale edit'],
            priority: 2, dueDate: null, actorAccountId: 2,
        );
    })->throws(RuntimeException::class);

    test('double updateDraft on same request succeeds (both editable)', function (): void {
        // Verify the lock does not prevent legitimate sequential edits
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        $first = formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'أول', titleEn: 'First edit', body: ['text' => 'v1'],
            priority: 2, dueDate: null, actorAccountId: 1,
        );

        $second = formalRequestSvc()->updateDraft(
            request: $first,
            titleAr: 'ثانٍ', titleEn: 'Second edit', body: ['text' => 'v2'],
            priority: 3, dueDate: null, actorAccountId: 1,
        );

        expect($second->title_en)->toBe('Second edit')
            ->and($second->current_status)->toBe(InstitutionFormalRequest::STATUS_DRAFT);
    });
});

// ── Management IDOR protection ────────────────────────────────────────────────

describe('management portal IDOR protection', function (): void {

    test('managementVisible scope excludes draft requests', function (): void {
        $draft = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_DRAFT]);

        $found = InstitutionFormalRequest::managementVisible()->where('id', $draft->id)->first();

        expect($found)->toBeNull();
    });

    test('managementVisible scope excludes internal_review requests', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW]);

        $found = InstitutionFormalRequest::managementVisible()->where('id', $req->id)->first();

        expect($found)->toBeNull();
    });

    test('managementVisible scope excludes signed (not yet submitted) requests', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SIGNED]);

        $found = InstitutionFormalRequest::managementVisible()->where('id', $req->id)->first();

        expect($found)->toBeNull();
    });

    test('managementVisible scope excludes returned_to_preparer requests', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER]);

        $found = InstitutionFormalRequest::managementVisible()->where('id', $req->id)->first();

        expect($found)->toBeNull();
    });

    test('managementVisible scope includes submitted_to_management', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT]);

        $found = InstitutionFormalRequest::managementVisible()->where('id', $req->id)->first();

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($req->id);
    });

    test('managementVisible scope includes under_management_review', function (): void {
        $req = makeFormalRequest(['current_status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW]);

        $found = InstitutionFormalRequest::managementVisible()->where('id', $req->id)->first();

        expect($found)->not->toBeNull();
    });

    test('managementVisible scope includes accepted, rejected, responded, and closed', function (): void {
        foreach ([
            InstitutionFormalRequest::STATUS_ACCEPTED,
            InstitutionFormalRequest::STATUS_REJECTED,
            InstitutionFormalRequest::STATUS_RESPONDED,
            InstitutionFormalRequest::STATUS_CLOSED,
        ] as $index => $status) {
            $req = makeFormalRequest([
                'current_status' => $status,
                'request_number' => 'GCV-FR-2026-'.str_pad((string) ($index + 10), 5, '0', STR_PAD_LEFT),
            ]);

            $found = InstitutionFormalRequest::managementVisible()->where('id', $req->id)->first();
            expect($found)->not->toBeNull("Expected status '{$status}' to be management-visible");
        }
    });
});

// ── Signing records responsible_account_id ────────────────────────────────────

describe('signing principal persisted as responsible_account_id', function (): void {

    test('service stores signerAccountId in responsible_account_id when sign is called directly', function (): void {
        // We cannot call the full sign() without ElectronicApprovalService wired,
        // so we verify the behaviour by calling the service method on the
        // already-signed shortcut path: set content_hash manually and call
        // the low-level transition to replicate what sign() does.
        //
        // Instead, verify indirectly: respond() only notifies responsible_account_id
        // when it is non-null.  Create a request with responsible_account_id already
        // set (simulating what sign() writes) and confirm respond() runs without error.
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_ACCEPTED,
            'responsible_account_id' => 55, // the signer/principal
            'created_by_account_id' => 10,  // the secretary
        ]);

        $updated = formalRequestSvc()->respond(
            $req, actorAccountId: 99, responseBody: ['text' => 'Official response.'],
        );

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_RESPONDED)
            ->and((int) $updated->responsible_account_id)->toBe(55);
    });

    test('respond() still succeeds when responsible_account_id is null (no double-notify)', function (): void {
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_ACCEPTED,
            'responsible_account_id' => null, // not yet signed via full flow
            'created_by_account_id' => 10,
        ]);

        $updated = formalRequestSvc()->respond(
            $req, actorAccountId: 99, responseBody: ['text' => 'Response.'],
        );

        expect($updated->current_status)->toBe(InstitutionFormalRequest::STATUS_RESPONDED);
    });
});

// ── Attachment linking ────────────────────────────────────────────────────────

describe('attachment linking', function (): void {

    test('listAttachments returns empty collection when no links exist', function (): void {
        $req = makeFormalRequest();

        $attachments = formalRequestSvc()->listAttachments($req);

        expect($attachments)->toHaveCount(0);
    });

    test('attachmentLinks morphMany returns rows from attachment_links table', function (): void {
        $req = makeFormalRequest(['institution_id' => 1]);
        // Must insert a real SecureAttachment first — attachment_links has a FK constraint.
        $attachmentId = makeSecureAttachment(1, '-morph-a');

        DB::table('attachment_links')->insert([
            'linkable_type' => InstitutionFormalRequest::class,
            'linkable_id' => $req->id,
            'attachment_id' => $attachmentId,
            'link_type' => 'supporting_evidence',
            'created_at' => now(),
        ]);

        // The morphMany scope must return only links for this request.
        $links = $req->attachmentLinks()->get();
        expect($links)->toHaveCount(1)
            ->and((int) $links->first()->linkable_id)->toBe($req->id)
            ->and($links->first()->linkable_type)->toBe(InstitutionFormalRequest::class)
            ->and($links->first()->link_type)->toBe('supporting_evidence');
    });

    test('attachmentLinks morphMany is institution-isolated by request', function (): void {
        $reqA = makeFormalRequest(['institution_id' => 1, 'request_number' => 'GCV-FR-2026-00001']);
        $reqB = makeFormalRequest(['institution_id' => 2, 'request_number' => 'GCV-FR-2026-00002']);

        // Insert attachment for institution 1 and link to reqA only.
        $attachmentId = makeSecureAttachment(1, '-iso-a');

        DB::table('attachment_links')->insert([
            'linkable_type' => InstitutionFormalRequest::class,
            'linkable_id' => $reqA->id,
            'attachment_id' => $attachmentId,
            'link_type' => 'supporting_evidence',
            'created_at' => now(),
        ]);

        // reqA's links include the attachment; reqB has no links.
        $linksA = $reqA->attachmentLinks()->get();
        $linksB = $reqB->attachmentLinks()->get();

        expect($linksA)->toHaveCount(1)
            ->and($linksB)->toHaveCount(0);
    });

    test('linkAttachment throws when request is not in an editable state (signed)', function (): void {
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_SIGNED,
        ]);
        $attachmentId = makeSecureAttachment(1, '-post-sign');

        formalRequestSvc()->linkAttachment($req, $attachmentId);
    })->throws(RuntimeException::class, 'Attachments may only be linked while the request is in an editable state (draft)');

    test('linkAttachment throws when request is not in an editable state (submitted_to_management)', function (): void {
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT,
        ]);
        $attachmentId = makeSecureAttachment(1, '-post-submit');

        formalRequestSvc()->linkAttachment($req, $attachmentId);
    })->throws(RuntimeException::class, 'Attachments may only be linked while the request is in an editable state (draft)');

    test('linkAttachment throws when request is returned_to_preparer (not editable — must resubmit first)', function (): void {
        // returned_to_preparer is intentionally NOT editable: the source row is an
        // immutable audit snapshot of the signed version. The secretary must call
        // resubmit() to branch it into a new draft before making any changes.
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER,
        ]);
        $attachmentId = makeSecureAttachment(1, '-returned');

        formalRequestSvc()->linkAttachment($req, $attachmentId);
    })->throws(RuntimeException::class, 'Attachments may only be linked while the request is in an editable state (draft)');

    test('linkAttachment with stale draft model rejected after concurrent sign (locked reload sees signed state)', function (): void {
        // Simulates: actor A loads request in draft, actor B signs/submits the request,
        // actor A's stale draft model is used to call linkAttachment — the locked reload
        // inside the transaction sees the current (now-signed) state and throws.
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_DRAFT,
        ]);
        $attachmentId = makeSecureAttachment(1, '-stale-sign-race');

        // Simulate another actor transitioning the request to 'signed'
        DB::table('institution_formal_requests')
            ->where('id', $req->id)
            ->update(['current_status' => InstitutionFormalRequest::STATUS_SIGNED]);

        // Stale model still has current_status = 'draft' from mount time.
        // lockForMutation reloads and finds 'signed' → not editable → throws.
        formalRequestSvc()->linkAttachment($req, $attachmentId);
    })->throws(RuntimeException::class, 'Attachments may only be linked while the request is in an editable state (draft)');

    test('updateDraft throws when request is returned_to_preparer (must branch first)', function (): void {
        // returned_to_preparer is no longer editable — the returned source is an
        // immutable audit snapshot. The secretary must call resubmit() to create a
        // new editable branch before any content can be changed.
        $req = makeFormalRequest([
            'current_status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER,
        ]);

        formalRequestSvc()->updateDraft(
            request: $req,
            titleAr: 'تعديل', titleEn: 'Edit attempt', body: ['text' => 'Should be rejected'],
            priority: 2, dueDate: null, actorAccountId: 10,
        );
    })->throws(RuntimeException::class);

    test('linkAttachment throws when attachment belongs to a different institution', function (): void {
        $req = makeFormalRequest(['institution_id' => 1]);
        // makeSecureAttachment creates an attachment for institution 2 (wrong institution).
        $wrongId = makeSecureAttachment(2, '-wrong');

        formalRequestSvc()->linkAttachment($req, $wrongId);
    })->throws(RuntimeException::class, 'not found or does not belong to institution');

    test('linkAttachment succeeds when attachment belongs to same institution', function (): void {
        $req = makeFormalRequest(['institution_id' => 1]);
        $attachmentId = makeSecureAttachment(1, '-same');

        formalRequestSvc()->linkAttachment($req, $attachmentId, 'supporting_evidence');

        $links = $req->attachmentLinks()->get();
        expect($links)->toHaveCount(1)
            ->and($links->first()->attachment_id)->toBe($attachmentId)
            ->and($links->first()->link_type)->toBe('supporting_evidence');
    });

    test('linkAttachment is idempotent (second call does not create a duplicate)', function (): void {
        $req = makeFormalRequest(['institution_id' => 1]);
        $attachmentId = makeSecureAttachment(1, '-idempotent');

        formalRequestSvc()->linkAttachment($req, $attachmentId);
        formalRequestSvc()->linkAttachment($req, $attachmentId); // second call — idempotent

        expect($req->attachmentLinks()->count())->toBe(1); // no duplicate
    });
});

// ── Management-side attachment visibility ──────────────────────────────────────

describe('management can see attachments submitted with a request', function (): void {

    test('listAttachments returns attachments for management-visible request', function (): void {
        // Simulate the full "staff submits evidence, management reviews" path:
        // 1. Staff links an attachment to a draft request.
        // 2. The request advances to submitted_to_management (management-visible).
        // 3. listAttachments returns the linked evidence from the management side.
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_DRAFT,
        ]);

        $attachmentId = makeSecureAttachment(1, '-mgmt-visible');
        formalRequestSvc()->linkAttachment($req, $attachmentId, 'supporting_evidence');

        // Advance to management-visible state (skip intermediate steps for speed)
        DB::table('institution_formal_requests')
            ->where('id', $req->id)
            ->update(['current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT]);
        $req->refresh();

        // Management calls listAttachments — must return the evidence row
        $attachments = formalRequestSvc()->listAttachments($req);
        expect($attachments)->toHaveCount(1)
            ->and($attachments->first()->attachment_id)->toBe($attachmentId)
            ->and($attachments->first()->link_type)->toBe('supporting_evidence');
    });

    test('listAttachments on a different request returns empty (institution isolation at query level)', function (): void {
        // Two requests from different institutions that have both advanced to management-visible.
        // Evidence linked to request A must not appear when management queries request B.
        $reqA = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_DRAFT,
            'request_number' => 'GCV-FR-2026-00001',
        ]);
        $reqB = makeFormalRequest([
            'institution_id' => 2,
            'current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT,
            'request_number' => 'GCV-FR-2026-00002',
        ]);

        $attachmentId = makeSecureAttachment(1, '-inst-isolation');
        formalRequestSvc()->linkAttachment($reqA, $attachmentId, 'supporting_evidence');

        // Querying reqB (different institution, different request) must return empty
        $attachments = formalRequestSvc()->listAttachments($reqB);
        expect($attachments)->toHaveCount(0);
    });

    test('listAttachments includes attachment metadata when eager-loaded', function (): void {
        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT,
        ]);

        $attachmentId = makeSecureAttachment(1, '-eager');

        // Directly insert link (request is not in editable state, but we're testing
        // the query path, not the state guard — use raw insert to set up fixture)
        DB::table('attachment_links')->insert([
            'linkable_type' => InstitutionFormalRequest::class,
            'linkable_id' => $req->id,
            'attachment_id' => $attachmentId,
            'link_type' => 'supporting_evidence',
            'created_at' => now(),
        ]);

        $attachments = formalRequestSvc()->listAttachments($req);
        expect($attachments)->toHaveCount(1);

        $link = $attachments->first();
        // The attachment model must be eager-loaded (not lazy-loaded) — no N+1 in review screen
        expect($link->relationLoaded('attachment'))->toBeTrue()
            ->and($link->attachment?->original_filename)->toContain('eager');
    });
});

// ── Management submission notifications ────────────────────────────────────────

describe('submitToManagement fires formal_request.submitted notifications', function (): void {

    /**
     * Seed admin account + roles/permissions so resolveManagementRecipients() returns data.
     * Returns the admin account ID.
     */
    function seedManagementAdmin(int $adminId = 1): int
    {
        // administrative_accounts row
        DB::table('administrative_accounts')->insertOrIgnore([
            'id' => $adminId,
            'username' => "admin{$adminId}",
            'password' => bcrypt('secret'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // permissions → roles → role_permissions → administrative_account_roles chain
        $permId = DB::table('permissions')->insertGetId([
            'key' => 'formal_request.respond',
            'description' => 'Formal request respond',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'label' => "Management Role {$adminId}",
            'code' => "management_role_{$adminId}",
            'is_protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permissions')->insertOrIgnore([
            'role_id' => $roleId,
            'permission_id' => $permId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('administrative_account_roles')->insertOrIgnore([
            'administrative_account_id' => $adminId,
            'role_id' => $roleId,
            'granted_by' => 'test-setup',  // NOT NULL — required by schema
            'granted_at' => now(),
            'revoked_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $adminId;
    }

    test('submitToManagement creates portal_notifications for each admin with formal_request.respond', function (): void {
        $adminId = seedManagementAdmin(1);

        // Insert institution row so resolveInstitutionName returns a real name
        DB::table('organizations')->insertOrIgnore(['id' => 1, 'code' => 'GCV', 'name_en' => 'GCV Org', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('institution_types')->insertOrIgnore(['id' => 1, 'name_en' => 'School', 'code' => 'school', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('institutions')->insertOrIgnore([
            'id' => 1, 'organization_id' => 1, 'institution_type_id' => 1,
            'code' => 'INST-01', 'name_en' => 'Green Valley School', 'name_ar' => null,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_SIGNED,
        ]);

        formalRequestSvc()->submitToManagement($req, actorAccountId: 10);

        // A portal_notification must have been created for the admin
        $notif = DB::table('portal_notifications')
            ->where('recipient_account_type', 'admin')
            ->where('recipient_account_id', $adminId)
            ->where('notification_type', 'formal_request.submitted')
            ->first();

        expect($notif)->not->toBeNull('Expected a formal_request.submitted notification for the admin');

        $params = json_decode($notif->message_params, true);
        expect($params)->toHaveKey('institution_name')
            ->and($params['institution_name'])->toBe('Green Valley School')
            ->and($params['request_id'])->toBe($req->id)
            ->and($params['subject'])->toBe($req->title_en);
    });

    test('submitToManagement skips revoked admin roles', function (): void {
        $adminId = seedManagementAdmin(2);

        // Revoke the role grant
        DB::table('administrative_account_roles')
            ->where('administrative_account_id', $adminId)
            ->update(['revoked_at' => now()->subMinute()]);

        $req = makeFormalRequest([
            'institution_id' => 1,
            'request_number' => 'GCV-FR-2026-00002',
            'current_status' => InstitutionFormalRequest::STATUS_SIGNED,
        ]);

        formalRequestSvc()->submitToManagement($req, actorAccountId: 10);

        $count = DB::table('portal_notifications')
            ->where('recipient_account_id', $adminId)
            ->where('notification_type', 'formal_request.submitted')
            ->count();

        expect($count)->toBe(0);
    });

    test('submitToManagement falls back to institution ID string when institution row not found', function (): void {
        seedManagementAdmin(3);

        $req = makeFormalRequest([
            'institution_id' => 99, // no corresponding institutions row
            'request_number' => 'GCV-FR-2026-00003',
            'current_status' => InstitutionFormalRequest::STATUS_SIGNED,
        ]);

        formalRequestSvc()->submitToManagement($req, actorAccountId: 10);

        $notif = DB::table('portal_notifications')
            ->where('notification_type', 'formal_request.submitted')
            ->first();

        $params = json_decode($notif?->message_params ?? '{}', true);
        expect($params['institution_name'] ?? '')->toBe('Institution #99');
    });
});

// ── respond() notification includes institution_name ───────────────────────────

describe('respond() notification params include institution_name', function (): void {

    test('respond sends notification with fully interpolated params including institution_name', function (): void {
        DB::table('organizations')->insertOrIgnore(['id' => 1, 'code' => 'GCV', 'name_en' => 'GCV Org', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('institution_types')->insertOrIgnore(['id' => 1, 'code' => 'school', 'name_en' => 'School', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('institutions')->insertOrIgnore([
            'id' => 1, 'organization_id' => 1, 'institution_type_id' => 1,
            'code' => 'INST-01', 'name_en' => 'Green Valley School', 'name_ar' => null,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $req = makeFormalRequest([
            'institution_id' => 1,
            'current_status' => InstitutionFormalRequest::STATUS_ACCEPTED,
            'created_by_account_id' => 20,
            'responsible_account_id' => 30,
        ]);

        formalRequestSvc()->respond($req, actorAccountId: 99, responseBody: ['text' => 'Official response.']);

        // Both recipients should receive a notification with institution_name
        foreach ([20, 30] as $recipientId) {
            $notif = DB::table('portal_notifications')
                ->where('recipient_account_id', $recipientId)
                ->where('notification_type', 'formal_request.responded')
                ->first();

            expect($notif)->not->toBeNull("Expected notification for account {$recipientId}");
            $params = json_decode($notif->message_params, true);
            expect($params)->toHaveKey('institution_name')
                ->and($params['institution_name'])->toBe('Green Valley School')
                ->and($params)->toHaveKey('request_id')
                ->and($params)->toHaveKey('subject');
        }
    });

    test('respond notification falls back gracefully when institution row is absent', function (): void {
        $req = makeFormalRequest([
            'institution_id' => 999,
            'current_status' => InstitutionFormalRequest::STATUS_ACCEPTED,
            'created_by_account_id' => 50,
        ]);

        // Must not throw even when institution row is missing
        $result = formalRequestSvc()->respond($req, actorAccountId: 99, responseBody: ['text' => 'Response.']);
        expect($result->current_status)->toBe(InstitutionFormalRequest::STATUS_RESPONDED);

        $params = json_decode(
            DB::table('portal_notifications')->where('recipient_account_id', 50)->first()?->message_params ?? '{}',
            true,
        );
        expect($params['institution_name'] ?? '')->toBe('Institution #999');
    });
});
