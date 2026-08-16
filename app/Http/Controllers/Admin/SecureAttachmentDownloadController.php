<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Attachments\Models\SecureAttachment;
use Modules\Authorization\Contracts\PolicyKernel;
use Modules\Authorization\Data\AuthorizationDecisionContext;
use Modules\Authorization\Data\PermissionKey;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a private attachment to an authenticated admin actor.
 *
 * SECURITY model:
 *   1. Requires an active admin guard session (auth:admin middleware on route).
 *   2. Loads the SecureAttachment by its UUID — opaque, server-generated ID.
 *   3. Resolves the admin's current role codes from `administrative_account_roles`
 *      (non-revoked rows only) before calling PolicyKernel.
 *   4. Checks `attachment.read` permission via PolicyKernel with the attachment's
 *      institution as scope and the resolved role codes.
 *   5. Only serves 'available' attachments (status = available).
 *   6. Emits an audit event on every successful download.
 *   7. Serves with Content-Disposition: attachment, sanitized filename, and
 *      security headers. No public URL is ever generated.
 */
class SecureAttachmentDownloadController extends Controller
{
    public function __construct(private readonly PolicyKernel $policyKernel) {}

    public function __invoke(Request $request, string $attachmentId): StreamedResponse
    {
        $account = auth('admin')->user();
        abort_if($account === null, 403);

        // Load the attachment by UUID — 404 if not found
        $attachment = SecureAttachment::find($attachmentId);
        abort_if($attachment === null, 404, 'Attachment not found.');

        // Only available (scanned-clean) attachments may be served
        abort_if(! $attachment->isAvailable(), 403, 'Attachment is not available for download.');

        // Resolve the admin's role codes from the authoritative grants table.
        // Never trust a model property; always read from the database.
        $roleCodes = DB::table('administrative_account_roles as ar')
            ->join('roles as r', 'r.id', '=', 'ar.role_id')
            ->where('ar.administrative_account_id', $account->getKey())
            ->whereNull('ar.revoked_at')
            ->pluck('r.code')
            ->toArray();

        // Permission check via PolicyKernel scoped to the attachment's institution.
        // accountStatus must be the string value of the AccountStatus enum.
        $context = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::ATTACHMENT_READ,
            accountId: (int) $account->getKey(),
            accountType: 'administrative',
            accountStatus: $account->status->value,
            institutionId: (int) $attachment->institution_id,
            roleCodesHeld: $roleCodes,
        );

        abort_if(! $this->policyKernel->allows($context), 403, 'You do not have permission to download this attachment.');

        // Verify the file exists on disk
        $disk = $attachment->storage_disk;
        abort_if(! Storage::disk($disk)->exists($attachment->storage_path), 404, 'Attachment file not found.');

        // Emit audit event (string-variable pattern — Attachments depends on Audit)
        $payloadClass = 'Modules\\Audit\\Data\\AuditEventPayload';
        $recorderClass = 'Modules\\Audit\\Contracts\\AuditRecorder';

        app($recorderClass)->record(new $payloadClass(
            actorType: 'administrative',
            sourceModule: 'Attachments',
            action: 'attachment.downloaded',
            actorAccountId: (int) $account->getKey(),
            portal: 'admin',
            institutionId: (int) $attachment->institution_id,
            subjectType: 'SecureAttachment',
            metadata: [
                'attachment_id' => $attachment->id,
                'classification' => $attachment->classification,
                'mime_type' => $attachment->mime_type,
            ],
        ));

        return Storage::disk($disk)->download(
            $attachment->storage_path,
            $attachment->original_filename,
            [
                'Content-Type' => $attachment->mime_type,
                'Content-Disposition' => 'attachment; filename="'.rawurlencode($attachment->original_filename).'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }
}
