<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Data\StaffAttendanceStatus;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\StaffAttendanceRecord;

/**
 * Correct a verified staff attendance record.
 *
 * AUDIT TRAIL
 * -----------
 * Each correction appends one row to `staff_attendance_correction_history`.
 * Rows are append-only — never updated or deleted.
 *
 * One correction per verification cycle is enforced: to correct again the
 * record must be re-verified first (which increments correction_cycle).
 *
 * Rules:
 *  1. The record must be verified.
 *  2. No prior history entry for the current correction_cycle.
 *  3. New status_code must be valid; reason required when status demands it.
 */
final class CorrectVerifiedStaffRecord
{
    public function __invoke(
        StaffAttendanceRecord $record,
        string $newStatusCode,
        ?string $reason,
        int $actorStaffProfileId,
        ?string $confirmedArrivedAt = null,
        ?string $confirmedDepartedAt = null,
    ): StaffAttendanceRecord {
        return DB::transaction(function () use (
            $record, $newStatusCode, $reason, $actorStaffProfileId,
            $confirmedArrivedAt, $confirmedDepartedAt,
        ): StaffAttendanceRecord {
            $locked = StaffAttendanceRecord::lockForUpdate()->findOrFail($record->id);

            if (! $locked->is_verified) {
                throw new StaffAttendanceException(
                    "Staff attendance record #{$record->id} is not verified. ".
                    'Use CreateDailyStaffRecord to update unverified records.'
                );
            }

            if (! StaffAttendanceStatus::isValid($newStatusCode)) {
                throw new StaffAttendanceException(
                    "'{$newStatusCode}' is not a valid staff attendance status code."
                );
            }

            if (StaffAttendanceStatus::requiresReason($newStatusCode) && empty(trim((string) $reason))) {
                throw new StaffAttendanceException(
                    "Status '{$newStatusCode}' requires a correction reason."
                );
            }

            // One-correction-per-cycle guard using append-only history table
            $alreadyCorrected = DB::table('staff_attendance_correction_history')
                ->where('staff_attendance_record_id', $locked->id)
                ->where('correction_cycle', $locked->correction_cycle)
                ->exists();

            if ($alreadyCorrected) {
                throw new StaffAttendanceException(
                    "Record #{$record->id} has already been corrected in correction cycle ".
                    "{$locked->correction_cycle}. Re-verify the record before correcting again."
                );
            }

            $now = now();

            // Append immutable history row
            DB::table('staff_attendance_correction_history')->insert([
                'staff_attendance_record_id' => $locked->id,
                'staff_profile_id' => $locked->staff_profile_id,
                'operational_period_id' => $locked->operational_period_id,
                'record_date' => $locked->record_date instanceof Carbon
                    ? $locked->record_date->toDateString()
                    : (string) $locked->record_date,
                'correction_cycle' => $locked->correction_cycle,
                'previous_status_code' => $locked->status_code,
                'previous_reason' => $locked->reason,
                'corrected_by_staff_profile_id' => $actorStaffProfileId,
                'corrected_at' => $now,
            ]);

            // NOTE: correction_cycle is NOT incremented here.
            // It is only advanced by VerifyStaffRecord (re-verification) so the
            // history entry for the current cycle stays discoverable as a guard
            // for subsequent correction attempts in the same window.

            // Write new values
            $locked->status_code = $newStatusCode;
            $locked->reason = empty(trim((string) $reason)) ? null : $reason;
            $locked->confirmed_arrived_at = StaffAttendanceStatus::allowsArrivalTime($newStatusCode)
                ? ($confirmedArrivedAt !== '' ? $confirmedArrivedAt : null)
                : null;
            $locked->confirmed_departed_at = StaffAttendanceStatus::allowsDepartureTime($newStatusCode)
                ? ($confirmedDepartedAt !== '' ? $confirmedDepartedAt : null)
                : null;
            $locked->source = 'correction';
            // Keep is_verified = true (correction doesn't un-verify; history tracks the change)
            $locked->save();

            return $locked;
        });
    }
}
