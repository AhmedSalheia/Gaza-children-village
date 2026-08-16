<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\MarkSheetStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;

/**
 * Secretary verifies a submitted mark sheet.
 *
 * Enforced rules:
 *  1. Sheet must be in submitted status.
 *  2. staffProfileId must hold marks.verify permission (caller's responsibility).
 *  3. The verifier must be in the same institution semester scope as the sheet.
 *     (Cross-institution verification is prevented at the Livewire layer.)
 *
 * Verification is a distinct step from approval. A verified sheet must still
 * be approved by a principal or deputy before it is published.
 */
final class VerifyMarkSheet
{
    public function __invoke(MarkSheet $sheet, int $staffProfileId): MarkSheet
    {
        if (! $sheet->status->canVerify()) {
            throw new MarksException(
                "Mark sheet #{$sheet->id} cannot be verified: current status is '{$sheet->status->value}'."
            );
        }

        $sheet->status = MarkSheetStatus::Verified->value;
        $sheet->verified_by_staff_profile_id = $staffProfileId;
        $sheet->verified_at = now();
        $sheet->save();

        return $sheet;
    }
}
