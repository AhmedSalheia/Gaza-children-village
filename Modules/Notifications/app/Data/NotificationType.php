<?php

declare(strict_types=1);

namespace Modules\Notifications\Data;

/**
 * Stable notification type catalogue (18 types).
 *
 * All production code MUST reference a constant here rather than raw strings.
 * Translation keys in lang/{locale}/notifications.php are keyed by these values.
 *
 * Format: domain.action
 */
final class NotificationType
{
    // -----------------------------------------------------------------
    // Guardian correction requests
    // -----------------------------------------------------------------

    /** Guardian submitted a correction request — notify relevant staff. */
    public const CORRECTION_REQUEST_SUBMITTED = 'correction_request.submitted';

    /** Staff approved a correction request — notify guardian. */
    public const CORRECTION_REQUEST_APPROVED = 'correction_request.approved';

    /** Staff rejected a correction request — notify guardian. */
    public const CORRECTION_REQUEST_REJECTED = 'correction_request.rejected';

    /** Approved correction has been applied to the record — notify guardian. */
    public const CORRECTION_REQUEST_APPLIED = 'correction_request.applied';

    // -----------------------------------------------------------------
    // Student document requests
    // -----------------------------------------------------------------

    /** Guardian/student submitted a document request — notify relevant staff. */
    public const DOCUMENT_REQUEST_SUBMITTED = 'document_request.submitted';

    /** Document request approved and queued for issuance. */
    public const DOCUMENT_REQUEST_APPROVED = 'document_request.approved';

    /** Document request rejected — notify requestor. */
    public const DOCUMENT_REQUEST_REJECTED = 'document_request.rejected';

    /** Document has been issued and is ready — notify requestor. */
    public const DOCUMENT_REQUEST_READY = 'document_request.ready';

    /** Document has been collected / issued to requestor. */
    public const DOCUMENT_REQUEST_ISSUED = 'document_request.issued';

    // -----------------------------------------------------------------
    // Formal institution requests
    // -----------------------------------------------------------------

    /** Formal institution request submitted and awaiting review. */
    public const FORMAL_REQUEST_SUBMITTED = 'formal_request.submitted';

    /** Response received for a formal institution request. */
    public const FORMAL_REQUEST_RESPONDED = 'formal_request.responded';

    /** Principal/deputy returned a formal request to the preparer for revision. */
    public const FORMAL_REQUEST_RETURNED = 'formal_request.returned';

    // -----------------------------------------------------------------
    // Mark sheets
    // -----------------------------------------------------------------

    /** Secretary returned a mark sheet to teacher for correction. */
    public const MARK_SHEET_RETURNED = 'mark_sheet.returned';

    /** Secretary verified a mark sheet — notifies teacher. */
    public const MARK_SHEET_VERIFIED = 'mark_sheet.verified';

    // -----------------------------------------------------------------
    // Attendance sheets
    // -----------------------------------------------------------------

    /** Secretary returned an attendance sheet to teacher for correction. */
    public const ATTENDANCE_SHEET_RETURNED = 'attendance_sheet.returned';

    /** Secretary verified an attendance sheet — notifies teacher. */
    public const ATTENDANCE_SHEET_VERIFIED = 'attendance_sheet.verified';

    // -----------------------------------------------------------------
    // Generic workflow transitions
    // -----------------------------------------------------------------

    /** A workflow instance moved to a new state (generic fallback type). */
    public const WORKFLOW_TRANSITION = 'workflow.transition';

    // -----------------------------------------------------------------
    // Background job / operation tracking
    // -----------------------------------------------------------------

    /** A queued operation completed successfully — notify actor. */
    public const OPERATION_COMPLETED = 'operation.completed';

    /** A queued operation failed — notify actor. */
    public const OPERATION_FAILED = 'operation.failed';

    // -----------------------------------------------------------------
    // All types
    // -----------------------------------------------------------------

    /** @return list<string> */
    public static function all(): array
    {
        $r = new \ReflectionClass(self::class);

        return array_values(
            array_filter(
                array_map(fn ($c) => $c->getValue(), $r->getReflectionConstants()),
                fn ($v) => is_string($v) && str_contains((string) $v, '.')
            )
        );
    }
}
