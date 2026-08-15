<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\AttendanceScanEvent;

/**
 * Secretary reviews a pending scan event — accepting or rejecting it.
 *
 * ACCEPT: Updates or creates the staff attendance record with the scanned
 *   arrival/departure time in the scanned_* columns (NOT confirmed_*).
 *   The secretary must separately confirm official times via CreateDailyStaffRecord.
 *   This maintains the invariant that QR scans are never auto-official.
 *
 * REJECT: Marks the event as rejected with a reason. No attendance change.
 *
 * Rules:
 *  1. The event must be in 'pending' status.
 *  2. Only one review per event (idempotent guard).
 */
final class ReviewScanEvent
{
    public function __invoke(
        AttendanceScanEvent $event,
        string $outcome,
        int $reviewerStaffProfileId,
        ?string $rejectionReason = null,
    ): AttendanceScanEvent {
        if (! in_array($outcome, ['accepted', 'rejected'], true)) {
            throw new StaffAttendanceException(
                "Invalid review outcome '{$outcome}'. Must be 'accepted' or 'rejected'."
            );
        }

        return DB::transaction(function () use ($event, $outcome, $reviewerStaffProfileId, $rejectionReason): AttendanceScanEvent {
            $locked = AttendanceScanEvent::lockForUpdate()->findOrFail($event->id);

            if (! $locked->isPending()) {
                throw new StaffAttendanceException(
                    "Scan event #{$event->id} has already been reviewed (status: {$locked->processing_status})."
                );
            }

            $now = now();

            $locked->processing_status             = $outcome;
            $locked->reviewed_by_staff_profile_id  = $reviewerStaffProfileId;
            $locked->reviewed_at                   = $now;

            if ($outcome === 'rejected') {
                $locked->rejection_reason = $rejectionReason;
            }

            $locked->save();

            if ($outcome === 'accepted') {
                // Update the scan times on the attendance record (informational — never official)
                $this->applyScannedTime($locked);
            }

            return $locked;
        });
    }

    /**
     * Write scanned arrival or departure time to the attendance record.
     *
     * Creates a minimal record if none exists, using 'scan_assisted' source.
     * scanned_arrived_at / scanned_departed_at are INFORMATIONAL ONLY.
     */
    private function applyScannedTime(AttendanceScanEvent $event): void
    {
        $scanDateStr = $event->scan_date instanceof \Carbon\Carbon
            ? $event->scan_date->toDateString()
            : (string) $event->scan_date;

        $existing = DB::table('staff_attendance_records')
            ->where('staff_profile_id', $event->staff_profile_id)
            ->where('operational_period_id', $event->operational_period_id)
            ->whereDate('record_date', $scanDateStr)
            ->lockForUpdate()
            ->first();

        $scannedTime = $event->scanned_at->toTimeString(); // HH:MM:SS

        if ($existing) {
            $updates = ['updated_at' => now()];

            if ($event->direction === 'arrival') {
                $updates['scanned_arrived_at'] = $scannedTime;
            } elseif ($event->direction === 'departure') {
                $updates['scanned_departed_at'] = $scannedTime;
            }

            DB::table('staff_attendance_records')
                ->where('id', $existing->id)
                ->update($updates);
        } else {
            // Resolve institution_semester_id for the new minimal record
            $period = DB::table('operational_periods')
                ->where('id', $event->operational_period_id)
                ->value('institution_semester_id');

            DB::table('staff_attendance_records')->insert([
                'staff_profile_id'         => $event->staff_profile_id,
                'institution_semester_id'  => $period ?? $event->institution_semester_id,
                'operational_period_id'    => $event->operational_period_id,
                'record_date'              => $scanDateStr,
                'status_code'              => null, // unfilled — secretary must enter
                'scanned_arrived_at'       => $event->direction === 'arrival'   ? $scannedTime : null,
                'scanned_departed_at'      => $event->direction === 'departure' ? $scannedTime : null,
                'is_verified'              => false,
                'correction_cycle'         => 0,
                'source'                   => 'scan_assisted',
                'creator_staff_profile_id' => $event->reviewed_by_staff_profile_id,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }
    }
}
