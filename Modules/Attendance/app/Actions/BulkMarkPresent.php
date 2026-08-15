<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Modules\Attendance\Data\StudentAttendanceStatus;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Mark all unfilled records on a draft/returned sheet as 'present'.
 *
 * Records that already have a status code are left unchanged, so partial
 * entry + bulk-mark is a natural workflow (fill exceptions first, then
 * bulk-mark the remainder as present).
 *
 * Returns the count of records that were updated.
 */
final class BulkMarkPresent
{
    public function __invoke(AttendanceSheet $sheet): int
    {
        $sheetStatus = $sheet->status instanceof SheetStatus
            ? $sheet->status
            : SheetStatus::from((string) $sheet->status);

        if (! $sheetStatus->isEditable()) {
            throw new AttendanceException(
                "Sheet #{$sheet->id} has status '{$sheetStatus->value}' ".
                'and is not editable. Bulk mark is only available for draft or returned sheets.'
            );
        }

        return AttendanceRecord::where('sheet_id', $sheet->id)
            ->whereNull('status_code')
            ->update(['status_code' => StudentAttendanceStatus::PRESENT]);
    }
}
