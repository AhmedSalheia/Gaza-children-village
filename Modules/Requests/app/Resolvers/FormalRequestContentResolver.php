<?php

declare(strict_types=1);

namespace Modules\Requests\Resolvers;

use Illuminate\Support\Facades\DB;
use Modules\Workflow\Contracts\SubjectContentResolverContract;

/**
 * Domain-owned content hash resolver for InstitutionFormalRequest subjects.
 *
 * The Workflow module's ElectronicApprovalService calls this at two points:
 *   1. At token issuance — to capture the content hash the signer is reviewing.
 *   2. At approval-recording — to recompute the current hash and compare with
 *      the one stored in the token. If the request body changed since the
 *      token was issued, the hashes differ and signing is rejected.
 *
 * Canonical content: id, version, request_type, title_ar, title_en, body JSON,
 * priority, due_date. These are the editable fields the signer reviews.
 * Institution and metadata columns are intentionally excluded (they cannot be
 * changed after creation).
 *
 * Hash contract: returns exactly 64 lowercase hex characters (SHA-256).
 */
final class FormalRequestContentResolver implements SubjectContentResolverContract
{
    public function computeCanonicalHash(string $subjectType, int $subjectId): string
    {
        if ($subjectType !== 'InstitutionFormalRequest') {
            throw new \InvalidArgumentException(
                "FormalRequestContentResolver does not support subject type '{$subjectType}'."
            );
        }

        $row = DB::table('institution_formal_requests')
            ->where('id', $subjectId)
            ->select('id', 'version', 'request_type', 'title_ar', 'title_en', 'body', 'priority', 'due_date')
            ->first();

        if ($row === null) {
            throw new \RuntimeException(
                "InstitutionFormalRequest #{$subjectId} not found for canonical content hash computation."
            );
        }

        // Deterministic canonical representation — same fields the signer reviews on screen.
        $canonical = [
            'id' => $row->id,
            'version' => $row->version,
            'request_type' => $row->request_type,
            'title_ar' => $row->title_ar,
            'title_en' => $row->title_en,
            'body' => $row->body,          // raw JSON string (consistent between calls)
            'priority' => $row->priority,
            'due_date' => $row->due_date,
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }
}
