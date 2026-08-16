<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\MarkSheetStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;

/**
 * Teacher submits a draft/returned mark sheet for secretary review.
 *
 * Enforced rules:
 *  1. Sheet must be in draft or returned status.
 *  2. If a window is attached, it must still be open.
 *  3. staffProfileId must correspond to the teacher on the teaching assignment.
 *     (The caller resolves this from the authenticated session.)
 *
 * After submit, the sheet is read-only for the teacher until returned or
 * fully processed.
 */
final class SubmitMarkSheet
{
    public function __invoke(MarkSheet $sheet, int $staffProfileId): MarkSheet
    {
        if (! $sheet->status->canSubmit()) {
            throw new MarksException(
                "Mark sheet #{$sheet->id} cannot be submitted: current status is '{$sheet->status->value}'."
            );
        }

        // Window enforcement — window must still be open at submission time
        if ($sheet->mark_entry_window_id !== null) {
            $window = $sheet->markEntryWindow;

            if ($window && ! $window->isCurrentlyOpen()) {
                throw new MarksException(
                    "Mark entry window '{$window->name_ar}' is closed; submission is no longer allowed."
                );
            }
        }

        $sheet->status = MarkSheetStatus::Submitted->value;
        $sheet->submitted_by_staff_profile_id = $staffProfileId;
        $sheet->submitted_at = now();
        $sheet->save();

        return $sheet;
    }
}
