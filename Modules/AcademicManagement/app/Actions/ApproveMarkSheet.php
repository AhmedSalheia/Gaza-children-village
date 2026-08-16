<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\MarkSheetStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;

/**
 * Principal or deputy approves a verified mark sheet.
 *
 * Enforced rules:
 *  1. Sheet must be in verified status.
 *  2. staffProfileId must hold marks.approve permission (caller's responsibility).
 *  3. Approval and verification must be by different staff profiles
 *     (four-eyes principle — enforcement at the application layer is best-effort;
 *     the DB records both IDs for audit).
 *
 * Approved sheets are ready for publication by a separate publication step
 * (results module — Task E). This action does NOT publish.
 */
final class ApproveMarkSheet
{
    public function __invoke(MarkSheet $sheet, int $staffProfileId): MarkSheet
    {
        if (! $sheet->status->canApprove()) {
            throw new MarksException(
                "Mark sheet #{$sheet->id} cannot be approved: current status is '{$sheet->status->value}'."
            );
        }

        // Four-eyes principle — the approver must differ from the verifier
        if ($sheet->verified_by_staff_profile_id !== null &&
            (int) $sheet->verified_by_staff_profile_id === $staffProfileId) {
            throw new MarksException(
                'The approver must be a different staff member than the verifier (four-eyes principle).'
            );
        }

        $sheet->status = MarkSheetStatus::Approved->value;
        $sheet->approved_by_staff_profile_id = $staffProfileId;
        $sheet->approved_at = now();
        $sheet->save();

        return $sheet;
    }
}
