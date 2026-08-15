<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AttendancePublicationSnapshot;

/**
 * Revoke a published attendance snapshot.
 *
 * Mirrors RevokeResultPublication: requires a reason, terminal, immediately
 * removes guardian visibility.
 *
 * @throws MarksException
 */
final class RevokeAttendanceSnapshot
{
    public function __invoke(
        AttendancePublicationSnapshot $snapshot,
        string $revokeReason,
        int $revokedByStaffProfileId,
    ): AttendancePublicationSnapshot {
        if ($snapshot->status !== 'published') {
            throw new MarksException(
                "Snapshot #{$snapshot->id} cannot be revoked: ".
                "status is '{$snapshot->status}'."
            );
        }

        if (trim($revokeReason) === '') {
            throw new MarksException('A revoke reason is required.');
        }

        $snapshot->status                       = 'revoked';
        $snapshot->revoked_at                   = now();
        $snapshot->revoke_reason                = $revokeReason;
        $snapshot->revoked_by_staff_profile_id  = $revokedByStaffProfileId;
        $snapshot->save();

        return $snapshot;
    }
}
