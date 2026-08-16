<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\StaffAttendanceRecord;

/**
 * Verify (or re-verify) a staff attendance record.
 *
 * FIRST VERIFICATION (is_verified = false):
 *   Sets is_verified=true, verified_at, verified_by_staff_profile_id.
 *   correction_cycle stays at 0 (the first correction window).
 *
 * RE-VERIFICATION (is_verified = true, after a correction was made):
 *   Sets verified_at/verified_by again AND increments correction_cycle.
 *   This opens a new correction window for a future CorrectVerifiedStaffRecord call.
 *   Calling this before any correction has been made (no history for current cycle)
 *   is a no-op on cycle — it just refreshes the verified timestamp.
 *
 * Rules:
 *   1. The record must have a filled status_code.
 *   2. Re-verification increments correction_cycle if the current cycle has been corrected.
 */
final class VerifyStaffRecord
{
    public function __invoke(
        StaffAttendanceRecord $record,
        int $actorStaffProfileId,
    ): StaffAttendanceRecord {
        return DB::transaction(function () use ($record, $actorStaffProfileId): StaffAttendanceRecord {
            $locked = StaffAttendanceRecord::lockForUpdate()->findOrFail($record->id);

            if ($locked->status_code === null) {
                throw new StaffAttendanceException(
                    "Staff attendance record #{$record->id} has no status and cannot be verified. ".
                    'Fill in the attendance status first.'
                );
            }

            // If re-verifying after a correction has been made in the current cycle,
            // advance the cycle counter to open the next correction window.
            if ($locked->is_verified) {
                $hasCorrectionInCurrentCycle = DB::table('staff_attendance_correction_history')
                    ->where('staff_attendance_record_id', $locked->id)
                    ->where('correction_cycle', $locked->correction_cycle)
                    ->exists();

                if ($hasCorrectionInCurrentCycle) {
                    $locked->correction_cycle++;
                }
            }

            $locked->is_verified = true;
            $locked->verified_at = now();
            $locked->verified_by_staff_profile_id = $actorStaffProfileId;
            $locked->save();

            return $locked;
        });
    }
}
