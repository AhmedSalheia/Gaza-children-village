<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\StaffQrCredential;

/**
 * Revoke a QR credential.
 *
 * Once revoked the credential is permanently inactive.
 * Scan events referencing this credential that are still pending
 * must be rejected — they cannot be accepted after revocation.
 *
 * Pending events are automatically rejected by this action.
 */
final class RevokeQrCredential
{
    public function __invoke(
        StaffQrCredential $credential,
        int $revokedByStaffProfileId,
    ): StaffQrCredential {
        return DB::transaction(function () use ($credential, $revokedByStaffProfileId): StaffQrCredential {
            $locked = StaffQrCredential::lockForUpdate()->findOrFail($credential->id);

            if (! $locked->is_active) {
                throw new StaffAttendanceException(
                    "Credential #{$credential->id} is already revoked."
                );
            }

            $locked->is_active = false;
            $locked->revoked_at = now();
            $locked->revoked_by_staff_profile_id = $revokedByStaffProfileId;
            $locked->save();

            // Auto-reject any pending scan events for this credential
            DB::table('attendance_scan_events')
                ->where('qr_credential_id', $locked->id)
                ->where('processing_status', 'pending')
                ->update([
                    'processing_status' => 'rejected',
                    'rejection_reason' => 'Credential was revoked.',
                    'reviewed_by_staff_profile_id' => $revokedByStaffProfileId,
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            return $locked;
        });
    }
}
