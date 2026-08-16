<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Verify (approve) an attendance sheet.
 *
 * Accepts both 'submitted' (initial secretary review) and 'reopened'
 * (re-verification after post-verification corrections via CorrectVerifiedAttendance).
 *
 * Rules:
 *  1. The sheet must be in 'submitted' or 'reopened' status (awaitingReview()).
 *  2. The sheet transitions to 'verified'; verified_at and verified_by are set.
 *     Once verified the sheet is immutable — only ReopenForCorrection can unlock it.
 */
final class VerifySheet
{
    public function __invoke(
        AttendanceSheet $sheet,
        int $actorStaffProfileId,
    ): AttendanceSheet {
        return DB::transaction(function () use ($sheet, $actorStaffProfileId): AttendanceSheet {
            $locked = AttendanceSheet::lockForUpdate()->findOrFail($sheet->id);

            $status = $locked->status instanceof SheetStatus
                ? $locked->status
                : SheetStatus::from((string) $locked->status);

            if (! $status->awaitingReview()) {
                throw new AttendanceException(
                    "Sheet #{$sheet->id} cannot be verified from status '{$status->value}'. ".
                    "Only 'submitted' or 'reopened' sheets can be verified."
                );
            }

            $locked->status = SheetStatus::Verified->value;
            $locked->verified_at = now();
            $locked->verified_by_staff_profile_id = $actorStaffProfileId;
            $locked->save();

            return $locked;
        });
    }
}
