<?php

declare(strict_types=1);

namespace Modules\Requests\Resolvers;

use Illuminate\Support\Facades\DB;
use Modules\Workflow\Contracts\SubjectContentResolverContract;

/**
 * Domain-owned content hash resolver for StudentCorrectionRequest subjects.
 *
 * The Workflow module's ElectronicApprovalService calls this at two points:
 *   1. At token issuance time (to capture what the approver is reviewing).
 *   2. At approval-recording time (to confirm the proposal hasn't changed since
 *      the token was issued — a stale-approval attack prevention).
 *
 * Canonical content is: request ID, field code, classification, student profile ID,
 * the proposed value of the most recent submission, and the submission sequence.
 * Because proposed_value may be encrypted, the comparison uses the raw stored
 * string consistently (same token-hash for the same stored byte sequence).
 *
 * Hash contract: returns exactly 64 lowercase hex characters (SHA-256).
 */
final class CorrectionRequestContentResolver implements SubjectContentResolverContract
{
    public function computeCanonicalHash(string $subjectType, int $subjectId): string
    {
        // Supports 'StudentCorrectionRequest' as the subject type.
        if ($subjectType !== 'StudentCorrectionRequest') {
            throw new \InvalidArgumentException(
                "CorrectionRequestContentResolver does not support subject type '{$subjectType}'."
            );
        }

        $row = DB::table('student_correction_requests as scr')
            ->join('correction_field_proposals as cfp', function ($join): void {
                $join->on('cfp.correction_request_id', '=', 'scr.id')
                    ->whereRaw(
                        'cfp.submission_sequence = (SELECT MAX(cfp2.submission_sequence) FROM correction_field_proposals AS cfp2 WHERE cfp2.correction_request_id = scr.id)'
                    );
            })
            ->where('scr.id', $subjectId)
            ->select(
                'scr.id',
                'scr.field_catalogue_code',
                'scr.classification',
                'scr.student_profile_id',
                'cfp.proposed_value',
                'cfp.submission_sequence',
            )
            ->first();

        if ($row === null) {
            throw new \RuntimeException(
                "StudentCorrectionRequest #{$subjectId} not found for canonical content hash computation."
            );
        }

        // Deterministic canonical representation — same fields the reviewer sees on screen.
        $canonical = [
            'id' => $row->id,
            'field_catalogue_code' => $row->field_catalogue_code,
            'classification' => $row->classification,
            'student_profile_id' => $row->student_profile_id,
            'proposed_value' => $row->proposed_value,   // raw stored bytes (possibly encrypted)
            'submission_sequence' => $row->submission_sequence,
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }
}
