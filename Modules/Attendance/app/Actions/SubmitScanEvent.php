<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\AttendanceScanEvent;

/**
 * Record an attendance scan event from a QR scan or manual token entry.
 *
 * This action is called by the public scan endpoint (rate-limited) and the
 * manual-entry form. It validates the submitted token against the stored HMAC
 * hashes and creates a pending scan event for secretary review.
 *
 * NEVER OFFICIAL: The scan event is always pending. It does NOT automatically
 * update attendance records — ReviewScanEvent must be called by a secretary.
 *
 * INSTITUTION GUARD: The credential holder must have an active assignment at
 * the institution that owns the operational period. A staff member's QR
 * credential cannot be used to check into a different institution's period.
 *
 * Replay prevention: only one pending event per (credential, period, date, direction).
 * A second scan in the same direction for the same period/date returns the
 * existing pending event rather than creating a duplicate.
 *
 * @return array{event: AttendanceScanEvent, is_duplicate: bool}
 */
final class SubmitScanEvent
{
    public function __invoke(
        string $plaintextToken,
        int $operationalPeriodId,
        string $direction = 'unknown',
        ?string $deviceFingerprint = null,
    ): array {
        // Compute HMAC to look up credential (O(1) lookup — no bcrypt iteration)
        $tokenHash = hash_hmac('sha256', $plaintextToken, config('app.key'));

        $credential = DB::table('staff_qr_credentials')
            ->where('token_hash', $tokenHash)
            ->where('is_active', true)
            ->select('id', 'staff_profile_id')
            ->first();

        if (! $credential) {
            throw new StaffAttendanceException(
                'Invalid or revoked QR credential.'
            );
        }

        $period = DB::table('operational_periods')
            ->where('id', $operationalPeriodId)
            ->select('id', 'institution_semester_id')
            ->first();

        if (! $period) {
            throw new StaffAttendanceException(
                "Operational period #{$operationalPeriodId} not found."
            );
        }

        // Resolve institution_id through institution_semester
        $institutionId = DB::table('institution_semesters')
            ->where('id', $period->institution_semester_id)
            ->value('institution_id');

        if (! $institutionId) {
            throw new StaffAttendanceException(
                "InstitutionSemester #{$period->institution_semester_id} not found."
            );
        }

        // Institution guard: credential holder must be assigned to this institution
        $staffBelongs = DB::table('staff_institution_assignments')
            ->where('staff_profile_id', $credential->staff_profile_id)
            ->where('institution_id', $institutionId)
            ->whereNull('ended_on')
            ->exists();

        if (! $staffBelongs) {
            throw new StaffAttendanceException(
                'Your QR credential is not valid for this institution\'s check-in point.'
            );
        }

        $scanDate = now()->toDateString();

        return DB::transaction(function () use (
            $credential, $period, $scanDate, $direction, $deviceFingerprint,
        ): array {
            // Replay prevention: the unique index covers (qr_credential_id,
            // operational_period_id, scan_date, direction) for ALL statuses.
            // We check for ANY existing event — not just pending — so that a
            // rescan after an accepted or rejected review returns the existing
            // row rather than attempting a duplicate insert (which would cause
            // a unique-constraint violation / HTTP 500 at the public endpoint).
            $existing = DB::table('attendance_scan_events')
                ->where('qr_credential_id', $credential->id)
                ->where('operational_period_id', $period->id)
                ->where('scan_date', $scanDate)
                ->where('direction', $direction)
                ->first();

            if ($existing) {
                return [
                    'event'        => AttendanceScanEvent::find($existing->id),
                    'is_duplicate' => true,
                ];
            }

            $event = new AttendanceScanEvent();
            $event->qr_credential_id          = $credential->id;
            $event->staff_profile_id           = $credential->staff_profile_id;
            $event->institution_semester_id    = $period->institution_semester_id;
            $event->operational_period_id      = $period->id;
            $event->scanned_at                 = now();
            $event->scan_date                  = $scanDate;
            $event->direction                  = $direction;
            $event->device_fingerprint         = $deviceFingerprint;
            $event->processing_status          = 'pending';
            $event->save();

            return [
                'event'        => $event,
                'is_duplicate' => false,
            ];
        });
    }
}
