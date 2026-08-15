<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Data\StaffAttendanceStatus;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\StaffAttendanceRecord;

/**
 * Create (or update if still unverified) a daily staff attendance record.
 *
 * Rules:
 *  1. Only one record per (staff_profile_id, operational_period_id, record_date).
 *  2. The operational_period's institution_semester must be mutable (open/draft).
 *  3. The staff member must have an active assignment at the period's institution.
 *  4. If a record already exists and is NOT verified, the action updates it.
 *  5. If a record already exists and IS verified, throws — use CorrectVerifiedStaffRecord.
 *  6. Status code must be valid; reason required when the status demands it.
 */
final class CreateDailyStaffRecord
{
    private const MUTABLE_STATUSES = ['open', 'draft'];

    public function __invoke(
        int $staffProfileId,
        int $operationalPeriodId,
        string $date,
        string $statusCode,
        ?string $reason,
        int $creatorStaffProfileId,
        ?string $confirmedArrivedAt = null,
        ?string $confirmedDepartedAt = null,
        string $source = 'manual_entry',
    ): StaffAttendanceRecord {
        // Resolve institution_semester_id and institution_id from operational_period
        $period = DB::table('operational_periods')
            ->where('id', $operationalPeriodId)
            ->select('id', 'institution_semester_id')
            ->first();

        if (! $period) {
            throw new StaffAttendanceException(
                "Operational period #{$operationalPeriodId} not found."
            );
        }

        $semesterId = (int) $period->institution_semester_id;

        // Semester mutability guard
        $semester = DB::table('institution_semesters')
            ->where('id', $semesterId)
            ->select('id', 'status', 'institution_id')
            ->first();

        if (! $semester || ! in_array((string) $semester->status, self::MUTABLE_STATUSES, true)) {
            $semStatus = $semester?->status ?? 'unknown';

            throw new StaffAttendanceException(
                "InstitutionSemester #{$semesterId} has status '{$semStatus}' ".
                'and does not accept attendance records. Only open or draft semesters are mutable.'
            );
        }

        $institutionId = (int) $semester->institution_id;

        // Institution membership guard — prevents recording attendance for staff
        // who are not assigned to this institution (cross-institution isolation).
        $staffBelongsToInstitution = DB::table('staff_institution_assignments')
            ->where('staff_profile_id', $staffProfileId)
            ->where('institution_id', $institutionId)
            ->whereNull('ended_on')
            ->exists();

        if (! $staffBelongsToInstitution) {
            throw new StaffAttendanceException(
                "Staff profile #{$staffProfileId} does not have an active assignment at ".
                "institution #{$institutionId}. Attendance can only be recorded for staff ".
                'assigned to the institution that owns this operational period.'
            );
        }

        // Validate status code
        if (! StaffAttendanceStatus::isValid($statusCode)) {
            throw new StaffAttendanceException(
                "'{$statusCode}' is not a valid staff attendance status code."
            );
        }

        if (StaffAttendanceStatus::requiresReason($statusCode) && empty(trim((string) $reason))) {
            throw new StaffAttendanceException(
                "Status '{$statusCode}' requires a reason to be supplied."
            );
        }

        return DB::transaction(function () use (
            $staffProfileId, $operationalPeriodId, $semesterId, $date,
            $statusCode, $reason, $creatorStaffProfileId,
            $confirmedArrivedAt, $confirmedDepartedAt, $source,
        ): StaffAttendanceRecord {
            $existing = StaffAttendanceRecord::where('staff_profile_id', $staffProfileId)
                ->where('operational_period_id', $operationalPeriodId)
                ->whereDate('record_date', $date)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->is_verified) {
                throw new StaffAttendanceException(
                    "Attendance record for staff #{$staffProfileId} on {$date} is already verified. ".
                    'Use CorrectVerifiedStaffRecord to make post-verification changes.'
                );
            }

            if ($existing) {
                $existing->status_code             = $statusCode;
                $existing->reason                  = empty(trim((string) $reason)) ? null : $reason;
                $existing->confirmed_arrived_at    = StaffAttendanceStatus::allowsArrivalTime($statusCode)
                    ? $confirmedArrivedAt
                    : null;
                $existing->confirmed_departed_at   = StaffAttendanceStatus::allowsDepartureTime($statusCode)
                    ? $confirmedDepartedAt
                    : null;
                $existing->source                  = $source;
                $existing->save();

                return $existing;
            }

            $record = new StaffAttendanceRecord();
            $record->staff_profile_id          = $staffProfileId;
            $record->institution_semester_id   = $semesterId;
            $record->operational_period_id     = $operationalPeriodId;
            $record->record_date               = $date;
            $record->status_code               = $statusCode;
            $record->reason                    = empty(trim((string) $reason)) ? null : $reason;
            $record->confirmed_arrived_at      = StaffAttendanceStatus::allowsArrivalTime($statusCode)
                ? $confirmedArrivedAt
                : null;
            $record->confirmed_departed_at     = StaffAttendanceStatus::allowsDepartureTime($statusCode)
                ? $confirmedDepartedAt
                : null;
            $record->is_verified               = false;
            $record->correction_cycle          = 0;
            $record->source                    = $source;
            $record->creator_staff_profile_id  = $creatorStaffProfileId;
            $record->save();

            return $record;
        });
    }
}
