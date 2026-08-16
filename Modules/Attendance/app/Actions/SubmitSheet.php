<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Submit an attendance sheet to the secretary queue for review.
 *
 * Rules:
 *  1. The sheet must be draft or returned (editable states).
 *  2. Every attendance record on the sheet must have a status_code set.
 *     Submitting with unfilled records is rejected — the teacher must fill
 *     or bulk-mark-present before submitting.
 *  3. The sheet status transitions to 'submitted' and submitted_at is set.
 */
final class SubmitSheet
{
    public function __invoke(
        AttendanceSheet $sheet,
        int $actorStaffProfileId,
    ): AttendanceSheet {
        return DB::transaction(function () use ($sheet): AttendanceSheet {
            $locked = AttendanceSheet::lockForUpdate()->findOrFail($sheet->id);

            $status = $locked->status instanceof SheetStatus
                ? $locked->status
                : SheetStatus::from((string) $locked->status);

            if (! $status->isEditable()) {
                throw new AttendanceException(
                    "Sheet #{$sheet->id} cannot be submitted from status '{$status->value}'. ".
                    'Only draft or returned sheets can be submitted.'
                );
            }

            // All records must be filled
            $unfilledCount = $locked->records()->whereNull('status_code')->count();

            if ($unfilledCount > 0) {
                throw new AttendanceException(
                    "Sheet #{$sheet->id} has {$unfilledCount} unfilled record(s). ".
                    'All students must have an attendance status before submitting.'
                );
            }

            $locked->status = SheetStatus::Submitted->value;
            $locked->submitted_at = now();
            $locked->save();

            return $locked;
        });
    }
}
