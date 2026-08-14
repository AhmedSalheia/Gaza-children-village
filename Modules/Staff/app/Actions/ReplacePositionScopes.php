<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Exceptions\PositionMutationDeniedException;
use Modules\Staff\Models\StaffPosition;
use Modules\Staff\Models\StaffPositionPeriod;

/**
 * Atomically replace all period-scope links for a position.
 *
 * Rules (F16):
 *  - The position must reference an InstitutionSemester (non-academic positions
 *    cannot have period links).
 *  - The semester must not be closed or archived.
 *  - Each supplied period ID must belong to the position's InstitutionSemester
 *    (verified by checking that the period's institution_semester_id matches).
 *  - An empty array removes all period links (position becomes period-restricted
 *    to nothing — callers must decide whether that is intentional).
 *  - Duplicate period IDs in the input are deduplicated silently.
 *  - Uses a DB transaction: delete old rows then insert new rows atomically.
 *
 * "All periods" must be represented by passing all active approved period IDs;
 * do not pass a wildcard or sentinel value.
 */
final class ReplacePositionScopes
{
    /**
     * @param  list<int>  $operationalPeriodIds
     * @return Collection<int, StaffPositionPeriod>
     */
    public function __invoke(StaffPosition $position, array $operationalPeriodIds): Collection
    {
        if ($position->institution_semester_id === null) {
            throw new PositionMutationDeniedException(
                'Period links are only allowed for positions that reference an InstitutionSemester.'
            );
        }

        $this->guardSemesterMutable($position->institution_semester_id);
        $this->guardPeriodsInSemester($position->institution_semester_id, $operationalPeriodIds);

        return DB::transaction(function () use ($position, $operationalPeriodIds): Collection {
            StaffPositionPeriod::where('staff_position_id', $position->id)->delete();

            $result = collect();
            $seen = [];

            foreach ($operationalPeriodIds as $periodId) {
                if (isset($seen[$periodId])) {
                    continue;
                }

                $seen[$periodId] = true;

                $link = new StaffPositionPeriod;
                $link->staff_position_id = $position->id;
                $link->operational_period_id = $periodId;
                $link->save();

                $result->push($link);
            }

            return $result;
        });
    }

    private function guardSemesterMutable(int $institutionSemesterId): void
    {
        $semesterClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semesterClass::findOrFail($institutionSemesterId);

        $statusClass = 'Modules\\AcademicCalendar\\Enums\\AcademicStatus';

        if (in_array($semester->status->value, [
            $statusClass::Closed->value,
            $statusClass::Archived->value,
        ], true)) {
            throw new PositionMutationDeniedException(
                'Cannot modify period scopes when the institution semester is closed or archived.'
            );
        }
    }

    /**
     * @param  list<int>  $periodIds
     */
    private function guardPeriodsInSemester(int $institutionSemesterId, array $periodIds): void
    {
        if (empty($periodIds)) {
            return;
        }

        $periodClass = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';

        // Verify that every supplied period belongs to the position's semester.
        $validCount = $periodClass::where('institution_semester_id', $institutionSemesterId)
            ->whereIn('id', array_unique($periodIds))
            ->count();

        if ($validCount !== count(array_unique($periodIds))) {
            throw new \InvalidArgumentException(
                'One or more supplied operational period IDs do not belong to the position\'s InstitutionSemester.'
            );
        }
    }
}
