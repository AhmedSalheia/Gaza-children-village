<?php

declare(strict_types=1);

namespace Modules\Documents\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Models\DocumentTemplateVersion;
use Modules\Documents\Models\IssuedDocument;
use Modules\Documents\Models\StudentDocumentRequest;
use Modules\Documents\Services\DocumentNumberService;
use Modules\Documents\Services\DocumentTemplateVersionService;
use Modules\Documents\Services\DocumentTypeRegistry;
use Modules\Documents\Services\EnrollmentSnapshotService;

/**
 * Queued job that generates a PDF document for an approved request.
 *
 * Concurrency and idempotency guarantees:
 *
 *  1. The entire job body runs inside a single DB transaction.
 *  2. The request row is locked with lockForUpdate() before any check.
 *     This serializes concurrent dispatches: only one job at a time can
 *     hold the lock for a given request_id.
 *  3. Inside the lock we check the current status:
 *     - Only `approved` and `generation_failed` are actionable.
 *     - Any other status (cancelled, issued, generating, rejected …)
 *       causes the job to exit without generating anything.
 *  4. The issued_documents table has a unique index on request_id,
 *     providing a DB-level backstop: a second INSERT for the same
 *     request_id will fail with a UniqueConstraintViolationException.
 *  5. The transition to `generating` is written inside the same
 *     transaction as the issued_documents INSERT, so the two updates
 *     are atomic.
 *
 * Error boundary:
 *   Any exception during generation (template not found, PDF engine
 *   error, storage failure) is caught; the request is left in
 *   `generation_failed` status with no partial issued_documents row.
 *
 * Storage:
 *   PDFs are stored on the 'private' disk at:
 *     documents/{year}/{institution_id}/{document_number}.pdf
 */
final class GenerateDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        public readonly int $requestId,
        public readonly int $approverAccountId,
    ) {}

    public function handle(
        DocumentTypeRegistry $typeRegistry,
        DocumentNumberService $numberService,
        EnrollmentSnapshotService $snapshotService,
        DocumentTemplateVersionService $versionService,
    ): void {
        try {
            DB::transaction(function () use ($typeRegistry, $numberService, $snapshotService, $versionService): void {
                // ── Lock the request row ──────────────────────────────────
                // This serializes concurrent dispatches: only one job can
                // proceed at a time for a given request_id.
                $request = DB::table('student_document_requests')
                    ->where('id', $this->requestId)
                    ->lockForUpdate()
                    ->first();

                if ($request === null) {
                    // Request was deleted; nothing to do
                    return;
                }

                // ── Gate: only approved/generation_failed are actionable ──
                // Any other status (cancelled, issued, generating, rejected)
                // means this job invocation must be a no-op.
                if (! in_array($request->status, [
                    StudentDocumentRequest::STATUS_APPROVED,
                    StudentDocumentRequest::STATUS_GENERATION_FAILED,
                ], true)) {
                    return;
                }

                // ── Mark generating ───────────────────────────────────────
                // Inside the same transaction so the transition and the
                // subsequent document creation are atomic.
                DB::table('student_document_requests')
                    ->where('id', $this->requestId)
                    ->update([
                        'status' => StudentDocumentRequest::STATUS_GENERATING,
                        'updated_at' => now(),
                    ]);

                // ── Generate and store ────────────────────────────────────
                $this->generateDocument(
                    $request,
                    $typeRegistry,
                    $numberService,
                    $snapshotService,
                    $versionService,
                );
            });
        } catch (\Throwable $e) {
            // Error boundary: mark failed without a partial document.
            // Use a separate transaction so the failure mark is not rolled back.
            DB::table('student_document_requests')
                ->where('id', $this->requestId)
                ->whereNotIn('status', [
                    StudentDocumentRequest::STATUS_ISSUED,
                    StudentDocumentRequest::STATUS_CANCELLED,
                    StudentDocumentRequest::STATUS_REJECTED,
                ])
                ->update([
                    'status' => StudentDocumentRequest::STATUS_GENERATION_FAILED,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            // Re-throw so the queue driver records the failure and can retry
            throw $e;
        }
    }

    /**
     * Perform the actual PDF generation and write the issued_documents row.
     *
     * Called inside an open transaction that already holds a lockForUpdate on
     * the student_document_requests row. Any exception here rolls the entire
     * transaction back and the catch block in handle() marks generation_failed.
     *
     * @param  object  $request  Raw DB row (from lockForUpdate query)
     */
    private function generateDocument(
        object $request,
        DocumentTypeRegistry $typeRegistry,
        DocumentNumberService $numberService,
        EnrollmentSnapshotService $snapshotService,
        DocumentTemplateVersionService $versionService,
    ): void {
        $institutionId = (int) $request->institution_id;
        $institutionSemesterId = $request->institution_semester_id
            ? (int) $request->institution_semester_id
            : null;

        // Resolve the document type metadata
        $docType = $typeRegistry->get($request->document_type_code);

        // Resolve the active template version for this institution/type
        $template = DB::table('document_templates')
            ->where('document_type_code', $request->document_type_code)
            ->where(function ($q) use ($institutionId): void {
                $q->where('institution_id', $institutionId)
                    ->orWhere(function ($q2): void {
                        $q2->whereNull('institution_id');
                    });
            })
            ->whereNotNull('active_version_id')
            ->orderByDesc('institution_id') // institution-specific takes priority
            ->first();

        if (! $template) {
            throw new \RuntimeException(
                "No active document template found for type '{$request->document_type_code}' ".
                "at institution #{$institutionId}."
            );
        }

        $templateVersion = DocumentTemplateVersion::findOrFail(
            (int) $template->active_version_id
        );

        // Generate the sequential document number (inside our transaction)
        $documentNumber = $numberService->next(
            typeCode: $request->document_type_code,
            institutionId: $institutionId,
            year: now()->year,
        );

        // Build data context from enrollment snapshot
        $context = $snapshotService->buildFromEnrollment(
            enrollmentId: (int) $request->enrollment_id,
            documentNumber: $documentNumber,
            documentTypeLabelAr: (string) ($docType->label_ar ?? ''),
            documentTypeLabelEn: (string) ($docType->label_en ?? ''),
            requestingGuardianAccountId: $request->requested_by_actor_type === 'guardian'
                ? (int) $request->requested_by_account_id
                : null,
        );

        // Render the PDF
        $pdfBytes = $versionService->renderPreviewPdf($templateVersion, $context);

        // Compute file hash and build storage path
        $sha256 = hash('sha256', $pdfBytes);
        $storagePath = sprintf(
            'documents/%d/%d/%s.pdf',
            now()->year,
            $institutionId,
            $documentNumber,
        );

        // Store on private disk
        Storage::disk('private')->put($storagePath, $pdfBytes);

        // Generate high-entropy verification code (64 hex chars)
        $verificationCode = bin2hex(random_bytes(32));
        $verificationHash = hash('sha256', $verificationCode);

        // Create issued_documents record.
        // The unique index on request_id acts as a final backstop if this
        // path is somehow reached twice for the same request.
        $issued = new IssuedDocument;
        $issued->document_number = $documentNumber;
        $issued->document_type_code = $request->document_type_code;
        $issued->enrollment_id = (int) $request->enrollment_id;
        $issued->student_profile_id = (int) $request->student_profile_id;
        $issued->institution_id = $institutionId;
        $issued->institution_semester_id = $institutionSemesterId;
        $issued->template_version_id = $templateVersion->id;
        $issued->request_id = (int) $request->id;
        $issued->locale = (string) $request->locale;
        $issued->approved_by_account_id = $this->approverAccountId;
        $issued->issued_at = now();
        $issued->verification_code = $verificationCode;
        $issued->verification_code_hash = $verificationHash;
        $issued->storage_path = $storagePath;
        $issued->file_sha256 = $sha256;
        $issued->save();

        // Mark request as issued — still inside the same transaction
        DB::table('student_document_requests')
            ->where('id', $request->id)
            ->update([
                'status' => StudentDocumentRequest::STATUS_ISSUED,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
