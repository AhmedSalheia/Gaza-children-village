<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Models\IssuedDocument;
use Modules\Documents\Models\StudentDocumentRequest;
use Modules\Documents\Services\DocumentRequestService;

uses(RefreshDatabase::class);

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeRequest(array $overrides = []): StudentDocumentRequest
{
    $request = new StudentDocumentRequest;
    $request->enrollment_id = $overrides['enrollment_id'] ?? 1;
    $request->student_profile_id = $overrides['student_profile_id'] ?? 1;
    $request->institution_id = $overrides['institution_id'] ?? 1;
    $request->requested_by_actor_type = $overrides['requested_by_actor_type'] ?? 'guardian';
    $request->requested_by_account_id = $overrides['requested_by_account_id'] ?? 1;
    $request->portal = $overrides['portal'] ?? 'guardian';
    $request->document_type_code = $overrides['document_type_code'] ?? 'proof_of_enrolment';
    $request->locale = $overrides['locale'] ?? 'ar';
    $request->status = $overrides['status'] ?? StudentDocumentRequest::STATUS_SUBMITTED;
    $request->submitted_at = $overrides['submitted_at'] ?? now();
    $request->save();

    return $request;
}

function seedTemplateVersion(): int
{
    $templateId = DB::table('document_templates')->insertGetId([
        'document_type_code' => 'proof_of_enrolment',
        'organization_id' => null,
        'institution_id' => null,
        'ar_available' => true,
        'en_available' => false,
        'approval_required' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return DB::table('document_template_versions')->insertGetId([
        'template_id' => $templateId,
        'version_number' => 1,
        'locale' => 'ar',
        'body' => '<p>Test</p>',
        'status' => 'active',
        'content_hash' => hash('sha256', '<p>Test</p>'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeIssuedDocument(StudentDocumentRequest $request, array $overrides = []): IssuedDocument
{
    $templateVersionId = $overrides['template_version_id'] ?? seedTemplateVersion();
    $code = $overrides['verification_code'] ?? bin2hex(random_bytes(32));

    $doc = new IssuedDocument;
    $doc->document_number = $overrides['document_number'] ?? 'GCV-POE-2026-00001';
    $doc->document_type_code = $overrides['document_type_code'] ?? 'proof_of_enrolment';
    $doc->enrollment_id = $request->enrollment_id;
    $doc->student_profile_id = $request->student_profile_id;
    $doc->institution_id = $request->institution_id;
    $doc->institution_semester_id = $overrides['institution_semester_id'] ?? null;
    $doc->template_version_id = $templateVersionId;
    $doc->request_id = $request->id;
    $doc->locale = $request->locale;
    $doc->approved_by_account_id = $overrides['approved_by_account_id'] ?? 1;
    $doc->issued_at = $overrides['issued_at'] ?? now();
    $doc->verification_code = $code;
    $doc->verification_code_hash = $overrides['verification_code_hash'] ?? hash('sha256', $code);
    $doc->storage_path = $overrides['storage_path'] ?? 'documents/2026/1/GCV-POE-2026-00001.pdf';
    $doc->file_sha256 = $overrides['file_sha256'] ?? hash('sha256', 'fake-pdf-content');
    $doc->cancelled_at = $overrides['cancelled_at'] ?? null;
    $doc->save();

    return $doc;
}

// ── Catalogue contract enforcement ───────────────────────────────────────────

describe('DocumentRequestService catalogue enforcement', function (): void {

    test('createAndSubmit rejects a portal not in allowed_requesters', function (): void {
        DB::table('document_type_catalogue')->insertOrIgnore([
            'code' => 'staff_only_doc',
            'label_ar' => 'وثيقة للموظفين فقط',
            'label_en' => 'Staff Only Document',
            'completeness_checks' => '[]',
            'required_context_keys' => '[]',
            'allowed_requesters' => '["staff"]', // guardian NOT allowed
            'public_verification' => false,
            'reissuable' => false,
            'validity_days' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $svc = app(DocumentRequestService::class);

        expect(fn () => $svc->createAndSubmit([
            'enrollment_id' => 1,
            'student_profile_id' => 1,
            'institution_id' => 1,
            'actor_type' => 'guardian',
            'actor_account_id' => 1,
            'portal' => 'guardian',
            'document_type_code' => 'staff_only_doc',
            'locale' => 'ar',
        ]))->toThrow(DomainException::class);
    });

    test('createAndSubmit rejects a nonexistent document type', function (): void {
        $svc = app(DocumentRequestService::class);

        expect(fn () => $svc->createAndSubmit([
            'enrollment_id' => 1,
            'student_profile_id' => 1,
            'institution_id' => 1,
            'actor_type' => 'guardian',
            'actor_account_id' => 1,
            'portal' => 'guardian',
            'document_type_code' => 'totally_fake_type',
            'locale' => 'ar',
        ]))->toThrow(DomainException::class);
    });
});

// ── State machine ─────────────────────────────────────────────────────────────

describe('DocumentRequestService state machine', function (): void {

    test('createAndSubmit stores a submitted request', function (): void {
        $svc = app(DocumentRequestService::class);

        // Seed the catalogue entry so allowed_requesters enforcement can check it
        DB::table('document_type_catalogue')->insertOrIgnore([
            'code' => 'proof_of_enrolment',
            'label_ar' => 'إثبات التسجيل',
            'label_en' => 'Proof of Enrolment',
            'completeness_checks' => '["active_enrollment"]',
            'required_context_keys' => '[]',
            'allowed_requesters' => '["guardian","staff"]',
            'public_verification' => true,
            'reissuable' => false,
            'validity_days' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = $svc->createAndSubmit([
            'enrollment_id' => 5,
            'student_profile_id' => 3,
            'institution_id' => 1,
            'actor_type' => 'guardian',
            'actor_account_id' => 10,
            'portal' => 'guardian',
            'document_type_code' => 'proof_of_enrolment',
            'locale' => 'ar',
        ]);

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_SUBMITTED)
            ->and($request->enrollment_id)->toBe(5)
            ->and($request->id)->toBeInt();

        $this->assertDatabaseHas('student_document_requests', [
            'id' => $request->id,
            'status' => 'submitted',
        ]);
    });

    test('secretary can start completeness check from submitted', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'submitted']);

        $svc->startCompletenessCheck($request, 99);

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_PENDING_COMPLETENESS);
        $this->assertDatabaseHas('student_document_requests', [
            'id' => $request->id,
            'status' => 'pending_completeness',
            'reviewed_by_account_id' => 99,
        ]);
    });

    test('markCompletenessResult passes when no failures', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'pending_completeness']);

        $svc->markCompletenessResult($request, [], 99);

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_COMPLETENESS_PASSED);
    });

    test('markCompletenessResult fails when failures present', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'pending_completeness']);

        $svc->markCompletenessResult($request, ['تاريخ ميلاد الطالب مفقود.'], 99);

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_COMPLETENESS_FAILED);
    });

    test('forwardForApproval moves completeness_passed to awaiting_approval', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'completeness_passed']);

        $svc->forwardForApproval($request, 99);

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_AWAITING_APPROVAL);
    });

    test('requestClarification moves submitted to pending_clarification', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'submitted']);

        $svc->requestClarification($request, 'يرجى تقديم وثيقة تثبت الهوية', 99);

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_PENDING_CLARIFICATION);
        $this->assertDatabaseHas('student_document_requests', [
            'id' => $request->id,
            'status' => 'pending_clarification',
            'clarification_reason' => 'يرجى تقديم وثيقة تثبت الهوية',
        ]);
    });

    test('provideClarification re-enters submitted from pending_clarification', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'pending_clarification']);

        $svc->provideClarification($request, 'لقد أرفقت الوثائق المطلوبة');

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_SUBMITTED);
    });

    test('reject moves awaiting_approval to rejected', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'awaiting_approval']);

        $svc->reject($request, 'البيانات غير مكتملة', 5);

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_REJECTED);
        $this->assertDatabaseHas('student_document_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'البيانات غير مكتملة',
        ]);
    });

    test('cancel works from any non-terminal status', function (): void {
        $svc = app(DocumentRequestService::class);

        foreach ([
            'submitted',
            'pending_completeness',
            'completeness_failed',
            'completeness_passed',
            'awaiting_approval',
            'pending_clarification',
        ] as $status) {
            $request = makeRequest(['status' => $status]);
            $svc->cancel($request, 'إلغاء بطلب ولي الأمر');

            expect($request->status)->toBe(StudentDocumentRequest::STATUS_CANCELLED);
        }
    });

    test('cancel throws RuntimeException for terminal status', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'issued']);

        expect(fn () => $svc->cancel($request, 'reason'))->toThrow(RuntimeException::class);
    });

    test('transition throws RuntimeException for wrong source status', function (): void {
        $svc = app(DocumentRequestService::class);
        $request = makeRequest(['status' => 'submitted']);

        // Cannot forward directly to approval without passing completeness
        expect(fn () => $svc->forwardForApproval($request, 99))->toThrow(RuntimeException::class);
    });
});

// ── IssuedDocument model helpers ──────────────────────────────────────────────

describe('IssuedDocument model', function (): void {

    test('isActive returns true when not cancelled', function (): void {
        $request = makeRequest(['status' => 'issued']);
        $doc = makeIssuedDocument($request);

        expect($doc->isActive())->toBeTrue();
    });

    test('isCancelled returns true when cancelled_at is set', function (): void {
        $request = makeRequest(['status' => 'issued']);
        $doc = makeIssuedDocument($request, ['cancelled_at' => now()]);

        expect($doc->isCancelled())->toBeTrue();
    });

    test('verificationSummary returns valid status and no PII', function (): void {
        $request = makeRequest(['status' => 'issued']);
        $doc = makeIssuedDocument($request, [
            'document_number' => 'GCV-POE-2026-00042',
        ]);

        $summary = $doc->verificationSummary();

        expect($summary['status'])->toBe('valid')
            ->and($summary['document_number'])->toBe('GCV-POE-2026-00042')
            ->and($summary)->not->toHaveKey('student_profile_id')
            ->and($summary)->not->toHaveKey('guardian')
            ->and($summary)->not->toHaveKey('enrollment_id');
    });

    test('verificationSummary returns cancelled status when cancelled', function (): void {
        $request = makeRequest(['status' => 'cancelled']);
        $doc = makeIssuedDocument($request, ['cancelled_at' => now()]);

        $summary = $doc->verificationSummary();

        expect($summary['status'])->toBe('cancelled');
    });
});

// ── StudentDocumentRequest helpers ────────────────────────────────────────────

describe('StudentDocumentRequest status helpers', function (): void {

    test('isTerminal returns true for terminal statuses', function (): void {
        foreach (StudentDocumentRequest::TERMINAL_STATUSES as $status) {
            $request = new StudentDocumentRequest;
            $request->status = $status;

            expect($request->isTerminal())->toBeTrue($status.' should be terminal');
        }
    });

    test('isTerminal returns false for non-terminal statuses', function (): void {
        foreach (['submitted', 'pending_completeness', 'completeness_passed', 'awaiting_approval'] as $status) {
            $request = new StudentDocumentRequest;
            $request->status = $status;

            expect($request->isTerminal())->toBeFalse($status.' should not be terminal');
        }
    });
});
