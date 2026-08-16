<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Return a submitted attendance sheet to the teacher for correction.
 *
 * Rules:
 *  1. The sheet must be in 'submitted' status (awaiting review).
 *  2. A non-empty return reason must be provided so the teacher knows what
 *     needs to be corrected.
 *  3. The sheet transitions to 'returned' status. The teacher can then edit
 *     records and resubmit.
 */
final class ReturnSheet
{
    public function __invoke(
        AttendanceSheet $sheet,
        string $reason,
        int $actorStaffProfileId,
    ): AttendanceSheet {
        if (empty(trim($reason))) {
            throw new AttendanceException('A return reason must be provided.');
        }

        return DB::transaction(function () use ($sheet, $reason): AttendanceSheet {
            $locked = AttendanceSheet::lockForUpdate()->findOrFail($sheet->id);

            $status = $locked->status instanceof SheetStatus
                ? $locked->status
                : SheetStatus::from((string) $locked->status);

            // Only 'submitted' sheets may be returned — not 'reopened'.
            // A reopened sheet is mid-correction by an authorised secretary/principal;
            // returning it would drop the correction audit trail and allow teachers
            // to edit records outside the CorrectVerifiedAttendance path.
            if ($status !== SheetStatus::Submitted) {
                throw new AttendanceException(
                    "Sheet #{$sheet->id} cannot be returned from status '{$status->value}'. ".
                    "Only 'submitted' sheets can be returned to the teacher."
                );
            }

            $locked->status = SheetStatus::Returned->value;
            $locked->return_reason = $reason;
            $locked->save();

            return $locked;
        });
    }
}
