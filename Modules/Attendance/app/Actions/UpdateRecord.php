<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Modules\Attendance\Data\StudentAttendanceStatus;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Update a single attendance record on an editable sheet.
 *
 * Rules:
 *  1. The sheet must be draft or returned (editable).
 *  2. The status_code must be a valid StudentAttendanceStatus code.
 *  3. If the status requires a reason, reason must be non-empty.
 *  4. arrived_at / departed_at are only stored when the status allows them.
 *  5. The record must belong to the given sheet.
 */
final class UpdateRecord
{
    public function __invoke(
        AttendanceSheet $sheet,
        int $enrollmentId,
        string $statusCode,
        ?string $reason = null,
        ?string $arrivedAt = null,
        ?string $departedAt = null,
        ?string $safeNote = null,
    ): AttendanceRecord {
        // Sheet state guard
        $sheetStatus = $sheet->status instanceof SheetStatus
            ? $sheet->status
            : SheetStatus::from((string) $sheet->status);

        if (! $sheetStatus->isEditable()) {
            throw new AttendanceException(
                "Attendance sheet #{$sheet->id} has status '{$sheetStatus->value}' ".
                'and is not editable. Only draft or returned sheets accept record updates.'
            );
        }

        // Status code validation
        if (! StudentAttendanceStatus::isValid($statusCode)) {
            throw new AttendanceException(
                "'{$statusCode}' is not a valid attendance status code."
            );
        }

        // Reason validation
        if (StudentAttendanceStatus::requiresReason($statusCode) && empty(trim((string) $reason))) {
            throw new AttendanceException(
                "Status '{$statusCode}' requires a reason to be supplied."
            );
        }

        // Find the record
        $record = AttendanceRecord::where('sheet_id', $sheet->id)
            ->where('enrollment_id', $enrollmentId)
            ->first();

        if (! $record) {
            throw new AttendanceException(
                "No attendance record found for enrollment #{$enrollmentId} on sheet #{$sheet->id}."
            );
        }

        $record->status_code = $statusCode;
        $record->reason      = empty(trim((string) $reason)) ? null : $reason;

        // Only store time fields when the status allows them
        $record->arrived_at  = StudentAttendanceStatus::allowsArrivalTime($statusCode)
            ? $arrivedAt
            : null;
        $record->departed_at = StudentAttendanceStatus::allowsDepartureTime($statusCode)
            ? $departedAt
            : null;

        $record->safe_note = $safeNote;
        $record->save();

        return $record;
    }
}
