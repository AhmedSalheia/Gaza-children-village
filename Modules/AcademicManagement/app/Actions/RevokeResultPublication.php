<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\ResultPublication;

/**
 * Revoke a published result publication.
 *
 * Enforced rules:
 *  1. Publication must be in 'published' status (already-revoked is idempotent error).
 *  2. A non-empty revoke_reason is required.
 *  3. The publication becomes invisible to guardians immediately after revocation.
 *  4. Revocation is terminal — a revoked publication cannot be un-revoked.
 *     To re-publish, use PublishResults which creates a new version.
 *
 * @throws MarksException
 */
final class RevokeResultPublication
{
    public function __invoke(
        ResultPublication $publication,
        string $revokeReason,
        int $revokedByStaffProfileId,
    ): ResultPublication {
        if ($publication->status !== 'published') {
            throw new MarksException(
                "Publication #{$publication->id} cannot be revoked: ".
                "status is '{$publication->status}'."
            );
        }

        if (trim($revokeReason) === '') {
            throw new MarksException('A revoke reason is required.');
        }

        $publication->status = 'revoked';
        $publication->revoked_at = now();
        $publication->revoke_reason = $revokeReason;
        $publication->revoked_by_staff_profile_id = $revokedByStaffProfileId;
        $publication->save();

        return $publication;
    }
}
