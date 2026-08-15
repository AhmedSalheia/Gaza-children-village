<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Reopen a verified sheet for post-verification correction.
 *
 * Only a principal, deputy_principal, or secretary holding
 * STUDENT_ATTENDANCE_CORRECT may call this. Authorization is enforced by the
 * Livewire component; the action enforces data-integrity rules only.
 *
 * Rules:
 *  1. The sheet must be 'verified'.
 *  2. The institution semester must still be mutable (open/draft).
 *  3. The sheet transitions to 'reopened'.
 *  4. All records' correction_cycle is incremented to signal a new correction
 *     cycle. CorrectVerifiedAttendance uses this to enforce one-correction-per-
 *     cycle while appending each prior value to the correction history table.
 *  5. Re-verification (back to verified) follows VerifySheet (accepts reopened).
 */
final class ReopenForCorrection
{
    private const MUTABLE_STATUSES = ['open', 'draft'];

    public function __invoke(
        AttendanceSheet $sheet,
        int $actorStaffProfileId,
    ): AttendanceSheet {
        return DB::transaction(function () use ($sheet): AttendanceSheet {
            $locked = AttendanceSheet::lockForUpdate()->findOrFail($sheet->id);

            $status = $locked->status instanceof SheetStatus
                ? $locked->status
                : SheetStatus::from((string) $locked->status);

            if (! $status->isTerminal()) {
                throw new AttendanceException(
                    "Sheet #{$sheet->id} cannot be reopened from status '{$status->value}'. ".
                    'Only verified sheets can be reopened for correction.'
                );
            }

            // Semester mutability guard
            $semester = DB::table('institution_semesters')
                ->where('id', $locked->institution_semester_id)
                ->select('id', 'status')
                ->first();

            if (! $semester || ! in_array((string) $semester->status, self::MUTABLE_STATUSES, true)) {
                $semStatus = $semester?->status ?? 'unknown';

                throw new AttendanceException(
                    "InstitutionSemester #{$locked->institution_semester_id} has status '{$semStatus}' ".
                    'and does not accept corrections. Reopening is only permitted for open or draft semesters.'
                );
            }

            $locked->status = SheetStatus::Reopened->value;
            $locked->save();

            // Advance the correction_cycle counter on all records in this sheet.
            // CorrectVerifiedAttendance reads this counter to enforce one-correction-
            // per-cycle and to tag the history row with the correct cycle number.
            DB::table('student_attendance_records')
                ->where('sheet_id', $locked->id)
                ->increment('correction_cycle');

            return $locked;
        });
    }
}
