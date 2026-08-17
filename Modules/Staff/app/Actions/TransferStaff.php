<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Exceptions\AssignmentOverlapException;
use Modules\Staff\Models\StaffInstitutionAssignment;
use Modules\Staff\Models\StaffProfile;

/**
 * Atomically transfer a staff member from their current institution to a new one.
 *
 * Transfer semantics:
 *  - Old assignment ends on transferDate - 1 day (inclusive).
 *  - New assignment begins on transferDate (inclusive).
 *  - The entire operation is a single DB transaction.
 *  - If the old assignment started on the same date as the transfer, the operation
 *    is rejected (the old interval would have zero or negative duration).
 *  - Target institution must be different from the current one.
 *  - Historical row is preserved; it is never updated or deleted.
 */
final class TransferStaff
{
    public function __invoke(
        StaffProfile $profile,
        int $targetInstitutionId,
        \DateTimeInterface $transferDate,
        string $closureReason,
        string $actor,
        ?string $context = null,
    ): StaffInstitutionAssignment {
        return DB::transaction(function () use (
            $profile,
            $targetInstitutionId,
            $transferDate,
            $closureReason,
            $actor,
            $context
        ): StaffInstitutionAssignment {
            $current = StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
                ->whereNull('ended_on')
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new \InvalidArgumentException(
                    'No active assignment found. Cannot transfer without a current assignment.'
                );
            }

            if ($current->institution_id === $targetInstitutionId) {
                throw new \InvalidArgumentException(
                    'Target institution is the same as the current institution.'
                );
            }

            $transferDateStr = $transferDate->format('Y-m-d');
            $currentStartStr = $current->started_on->format('Y-m-d');

            // If the transfer date equals the assignment's start date, the closed
            // interval would be empty or negative — reject and require correction.
            if ($transferDateStr <= $currentStartStr) {
                throw new \InvalidArgumentException(
                    'Transfer date must be after the current assignment start date. '.
                    'Use correction or cancellation for same-day changes.'
                );
            }

            // The old assignment ends the day before the transfer.
            $lastDayOfOld = Carbon::parse($transferDateStr)->subDay()->format('Y-m-d');

            // Close the old assignment.
            $current->ended_on = $lastDayOfOld;
            $current->closure_reason = $closureReason;
            $current->source_actor = $actor;
            $current->source_context = $context;
            $current->save();

            // Check for overlaps with any other existing assignments at the target.
            // Use whereDate() so SQLite date columns compare correctly against 'Y-m-d' strings.
            $overlapping = StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
                ->where('id', '!=', $current->id)
                ->whereDate('started_on', '<=', $transferDateStr)
                ->where(function ($q) use ($transferDateStr): void {
                    $q->whereNull('ended_on')
                        ->orWhereDate('ended_on', '>=', $transferDateStr);
                })
                ->exists();

            if ($overlapping) {
                throw new AssignmentOverlapException(
                    'The new assignment from the transfer date overlaps with an existing assignment.'
                );
            }

            // Open the new assignment.
            $newAssignment = new StaffInstitutionAssignment;
            $newAssignment->staff_profile_id = $profile->id;
            $newAssignment->institution_id = $targetInstitutionId;
            $newAssignment->started_on = $transferDateStr;
            $newAssignment->ended_on = null;
            $newAssignment->source_actor = $actor;
            $newAssignment->source_context = $context;
            $newAssignment->save();

            return $newAssignment;
        });
    }
}
