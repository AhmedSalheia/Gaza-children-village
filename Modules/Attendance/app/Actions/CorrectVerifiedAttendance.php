<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Data\StudentAttendanceStatus;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Correct an individual attendance record on a reopened sheet.
 *
 * AUDIT TRAIL
 * -----------
 * Each correction appends one row to `student_attendance_correction_history`,
 * capturing the previous status/reason, the cycle number, actor, and timestamp.
 * This table is append-only — rows are never updated or deleted.
 *
 * One correction per reopen cycle is enforced by checking whether the history
 * table already contains a row for (record_id, correction_cycle). If it does,
 * the secretary must re-verify the sheet (VerifySheet, which accepts 'reopened')
 * and call ReopenForCorrection again to start a new cycle before correcting further.
 *
 * Rules:
 *  1. The sheet must be in 'reopened' status.
 *  2. The new status_code must be a valid StudentAttendanceStatus code.
 *  3. If the new status requires a reason, reason must be non-empty.
 *  4. arrived_at / departed_at are accepted as explicit inputs and stored only
 *     when the new status allows them; old time values are NOT carried over.
 *  5. The record must belong to the given sheet.
 *  6. No prior history entry may exist for this record in this correction_cycle.
 *
 * After corrections, the secretary calls VerifySheet to re-close the sheet.
 */
final class CorrectVerifiedAttendance
{
    public function __invoke(
        AttendanceSheet $sheet,
        int $enrollmentId,
        string $newStatusCode,
        ?string $reason,
        int $actorStaffProfileId,
        ?string $arrivedAt = null,
        ?string $departedAt = null,
    ): AttendanceRecord {
        return DB::transaction(function () use (
            $sheet, $enrollmentId, $newStatusCode, $reason,
            $actorStaffProfileId, $arrivedAt, $departedAt,
        ): AttendanceRecord {
            $locked = AttendanceSheet::lockForUpdate()->findOrFail($sheet->id);

            $status = $locked->status instanceof SheetStatus
                ? $locked->status
                : SheetStatus::from((string) $locked->status);

            if (! $status->allowsCorrection()) {
                throw new AttendanceException(
                    "Sheet #{$sheet->id} has status '{$status->value}' and does not allow corrections. ".
                    'Call ReopenForCorrection first.'
                );
            }

            if (! StudentAttendanceStatus::isValid($newStatusCode)) {
                throw new AttendanceException(
                    "'{$newStatusCode}' is not a valid attendance status code."
                );
            }

            if (StudentAttendanceStatus::requiresReason($newStatusCode) && empty(trim((string) $reason))) {
                throw new AttendanceException(
                    "Status '{$newStatusCode}' requires a correction reason."
                );
            }

            $record = AttendanceRecord::where('sheet_id', $locked->id)
                ->where('enrollment_id', $enrollmentId)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw new AttendanceException(
                    "No attendance record found for enrollment #{$enrollmentId} on sheet #{$sheet->id}."
                );
            }

            // One-correction-per-cycle guard: check append-only history table.
            // Using the history table (not the mutable record columns) ensures
            // the guard survives multiple reopen cycles correctly.
            $alreadyCorrectedThisCycle = DB::table('student_attendance_correction_history')
                ->where('attendance_record_id', $record->id)
                ->where('correction_cycle', $record->correction_cycle)
                ->exists();

            if ($alreadyCorrectedThisCycle) {
                throw new AttendanceException(
                    "Record for enrollment #{$enrollmentId} on sheet #{$sheet->id} has already been ".
                    "corrected in correction cycle {$record->correction_cycle}. ".
                    'Re-verify the sheet and reopen again before making further corrections.'
                );
            }

            $now = now();

            // Append to immutable history: captures the PREVIOUS value before overwriting.
            DB::table('student_attendance_correction_history')->insert([
                'attendance_record_id'          => $record->id,
                'sheet_id'                      => $locked->id,
                'enrollment_id'                 => $enrollmentId,
                'correction_cycle'              => $record->correction_cycle,
                'previous_status_code'          => $record->status_code,
                'previous_reason'               => $record->reason,
                'corrected_by_staff_profile_id' => $actorStaffProfileId,
                'corrected_at'                  => $now,
            ]);

            // Write new values to the record.
            // previous_status_code / previous_reason on the record reflect the
            // most recent correction only (quick display); full history is in
            // student_attendance_correction_history.
            $record->previous_status_code          = $record->status_code;
            $record->previous_reason               = $record->reason;
            $record->status_code                   = $newStatusCode;
            $record->reason                        = empty(trim((string) $reason)) ? null : $reason;
            $record->arrived_at                    = StudentAttendanceStatus::allowsArrivalTime($newStatusCode)
                ? ($arrivedAt !== '' ? $arrivedAt : null)
                : null;
            $record->departed_at                   = StudentAttendanceStatus::allowsDepartureTime($newStatusCode)
                ? ($departedAt !== '' ? $departedAt : null)
                : null;
            $record->source                        = 'correction';
            $record->corrected_by_staff_profile_id = $actorStaffProfileId;
            $record->corrected_at                  = $now;
            $record->save();

            return $record;
        });
    }
}
