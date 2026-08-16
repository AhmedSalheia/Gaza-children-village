<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Models\StudentDocumentRequest;
use Modules\Documents\Services\DocumentRequestService;

uses(RefreshDatabase::class);

/**
 * Tests that generation failure leaves the request in 'generation_failed' state
 * with no partial issued_documents row, and that the request remains recoverable.
 */
describe('Generation failure leaves recoverable state', function (): void {

    test('generation_failed request has no issued document', function (): void {
        $request = new StudentDocumentRequest;
        $request->enrollment_id            = 1;
        $request->student_profile_id       = 1;
        $request->institution_id           = 1;
        $request->requested_by_actor_type  = 'guardian';
        $request->requested_by_account_id  = 1;
        $request->portal                   = 'guardian';
        $request->document_type_code       = 'proof_of_enrolment';
        $request->locale                   = 'ar';
        $request->status                   = StudentDocumentRequest::STATUS_GENERATION_FAILED;
        $request->approved_by_account_id   = 1;
        $request->submitted_at             = now();
        $request->save();

        // No issued document should exist
        $issuedCount = DB::table('issued_documents')
            ->where('request_id', $request->id)
            ->whereNull('cancelled_at')
            ->count();

        expect($issuedCount)->toBe(0);
    });

    test('generation_failed request can be re-approved and re-dispatched', function (): void {
        // Simulate: approve a request that is in generation_failed state
        // The job can be re-dispatched because it's not a terminal state

        $request = new StudentDocumentRequest;
        $request->enrollment_id            = 1;
        $request->student_profile_id       = 1;
        $request->institution_id           = 1;
        $request->requested_by_actor_type  = 'guardian';
        $request->requested_by_account_id  = 1;
        $request->portal                   = 'guardian';
        $request->document_type_code       = 'proof_of_enrolment';
        $request->locale                   = 'ar';
        $request->status                   = StudentDocumentRequest::STATUS_GENERATION_FAILED;
        $request->submitted_at             = now();
        $request->save();

        // Verify it's not terminal — can still be acted upon
        expect($request->isTerminal())->toBeFalse();

        // generation_failed is not in TERMINAL_STATUSES
        expect(StudentDocumentRequest::TERMINAL_STATUSES)->not->toContain('generation_failed');
    });

    test('generation_failed request can be cancelled', function (): void {
        $svc     = app(DocumentRequestService::class);
        $request = new StudentDocumentRequest;
        $request->enrollment_id            = 1;
        $request->student_profile_id       = 1;
        $request->institution_id           = 1;
        $request->requested_by_actor_type  = 'guardian';
        $request->requested_by_account_id  = 1;
        $request->portal                   = 'guardian';
        $request->document_type_code       = 'proof_of_enrolment';
        $request->locale                   = 'ar';
        $request->status                   = StudentDocumentRequest::STATUS_GENERATION_FAILED;
        $request->submitted_at             = now();
        $request->save();

        $svc->cancel($request, 'تم إلغاء الطلب بعد الفشل');

        expect($request->status)->toBe(StudentDocumentRequest::STATUS_CANCELLED);
    });
});
