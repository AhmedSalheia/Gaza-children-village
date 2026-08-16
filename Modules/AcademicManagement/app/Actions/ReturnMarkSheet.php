<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\MarkSheetStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;

/**
 * Secretary returns a submitted mark sheet to the teacher.
 *
 * Enforced rules:
 *  1. Sheet must be in submitted status.
 *  2. A non-empty reason must be provided.
 *  3. staffProfileId must hold marks.return permission (caller's responsibility).
 *
 * After return, the sheet reverts to draft status and the teacher may
 * edit marks again within the window.
 */
final class ReturnMarkSheet
{
    public function __invoke(
        MarkSheet $sheet,
        string $reason,
        int $staffProfileId,
    ): MarkSheet {
        if (! $sheet->status->canReturn()) {
            throw new MarksException(
                "Mark sheet #{$sheet->id} cannot be returned: current status is '{$sheet->status->value}'."
            );
        }

        if (trim($reason) === '') {
            throw new MarksException('A return reason is required.');
        }

        $sheet->status = MarkSheetStatus::Returned->value;
        $sheet->returned_by_staff_profile_id = $staffProfileId;
        $sheet->returned_at = now();
        $sheet->return_reason = $reason;
        $sheet->save();

        return $sheet;
    }
}
