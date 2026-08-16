<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

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
 * Serves a private attachment to an authenticated staff actor.
 *
 * SECURITY model:
 *   1. Requires an active staff guard session (auth:staff middleware on route).
 *   2. Loads the SecureAttachment by its UUID — opaque, server-generated ID.
 *   3. Resolves the staff member's role codes from their active position in the
 *      attachment's institution:
 *        a. Find the active staff_positions row for this profile + institution.
 *        b. Look up position_role_grants → roles for that position_definition.
 *      If no active position exists in that institution, role codes are empty
 *      and PolicyKernel denies the request — enforcing cross-institution isolation.
 *   4. Checks `attachment.read` permission via PolicyKernel with resolved role codes.
 *   5. Only serves 'available' attachments (status = available).
 *   6. Emits an audit event on every successful download.
 *   7. Serves with Content-Disposition: attachment and security headers.
 *      No public URL is ever generated.
 */
class SecureAttachmentDownloadController extends Controller
{
    public function __construct(private readonly PolicyKernel $policyKernel) {}

    public function __invoke(Request $request, string $attachmentId): StreamedResponse
    {
        $account = auth('staff')->user();
        abort_if($account === null, 403);

        $attachment = SecureAttachment::find($attachmentId);
        abort_if($attachment === null, 404, 'Attachment not found.');

        abort_if(! $attachment->isAvailable(), 403, 'Attachment is not available for download.');

        // Resolve the staff member's active position in the attachment's institution.
        // The position is institution-scoped: no position in the target institution
        // means no role codes, which causes PolicyKernel to deny the request.
        // This is the primary cross-institution isolation mechanism for staff.
        $staffProfileId = $account->staff_profile_id ?? null;

        // Resolve ALL active position_definitions for this profile in the target
        // institution. A staff member may hold multiple simultaneous positions
        // (e.g. teacher + homeroom coordinator); union their role codes so any
        // permission granted through any active position is honoured.
        $positionDefinitions = $staffProfileId !== null ? DB::table('staff_positions')
            ->where('staff_profile_id', $staffProfileId)
            ->where('institution_id', (int) $attachment->institution_id)
            ->where('started_on', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('ended_on')->orWhere('ended_on', '>=', now()->toDateString()))
            ->pluck('position_definition')
            ->toArray() : [];

        // Union role codes across all active positions.
        // Empty array → PolicyKernel denies at step 6 (no roles = no grants).
        $roleCodes = ! empty($positionDefinitions) ? DB::table('position_role_grants as prg')
            ->join('roles as r', 'r.id', '=', 'prg.role_id')
            ->whereIn('prg.position_definition', $positionDefinitions)
            ->distinct()
            ->pluck('r.code')
            ->toArray() : [];

        $context = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::ATTACHMENT_READ,
            accountId: (int) $account->getKey(),
            accountType: 'staff',
            accountStatus: $account->status->value,
            institutionId: (int) $attachment->institution_id,
            roleCodesHeld: $roleCodes,
        );

        abort_if(! $this->policyKernel->allows($context), 403, 'You do not have permission to download this attachment.');

        $disk = $attachment->storage_disk;
        abort_if(! Storage::disk($disk)->exists($attachment->storage_path), 404, 'Attachment file not found.');

        // Emit audit event (string-variable pattern — Attachments depends on Audit)
        $payloadClass = 'Modules\\Audit\\Data\\AuditEventPayload';
        $recorderClass = 'Modules\\Audit\\Contracts\\AuditRecorder';

        app($recorderClass)->record(new $payloadClass(
            actorType: 'staff',
            sourceModule: 'Attachments',
            action: 'attachment.downloaded',
            actorAccountId: (int) $account->getKey(),
            portal: 'staff',
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
