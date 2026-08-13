<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Data\CreateOperationalPeriodData;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;
use RuntimeException;

/**
 * Add an operational period to a draft institution semester.
 *
 * Validates:
 *   - The institution semester is Draft (mutation blocked once opened).
 *   - Sequence is a positive integer.
 *   - starts_at is earlier than ends_at (overnight not supported in F08).
 *   - Code is unique within the institution semester.
 *   - Sequence is unique within the institution semester.
 *   - Active periods in the same institution semester do not overlap with the
 *     new period (adjacent boundaries — same start as another's end — are allowed).
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class AddOperationalPeriod
{
    public function execute(InstitutionSemester $is, CreateOperationalPeriodData $data): OperationalPeriod
    {
        if ($is->status !== AcademicStatus::Draft) {
            throw new RuntimeException(
                "Operational periods can only be added to a draft institution semester. Current status: {$is->status->value}."
            );
        }

        if ($data->sequence < 1) {
            throw new RuntimeException(
                "Sequence must be a positive integer. Got: {$data->sequence}."
            );
        }

        if (strtotime($data->startsAt) >= strtotime($data->endsAt)) {
            throw new RuntimeException(
                "starts_at must be earlier than ends_at (overnight periods not supported in F08). Got: {$data->startsAt}–{$data->endsAt}."
            );
        }

        $codeExists = OperationalPeriod::where('institution_semester_id', $is->id)
            ->where('code', $data->code)
            ->exists();

        if ($codeExists) {
            throw new RuntimeException(
                "Code '{$data->code}' already exists in this institution semester."
            );
        }

        $sequenceExists = OperationalPeriod::where('institution_semester_id', $is->id)
            ->where('sequence', $data->sequence)
            ->exists();

        if ($sequenceExists) {
            throw new RuntimeException(
                "Sequence {$data->sequence} already exists in this institution semester."
            );
        }

        $this->assertNoOverlap($is->id, $data->startsAt, $data->endsAt);

        $period = new OperationalPeriod;
        $period->institution_semester_id = $is->id;
        $period->code = $data->code;
        $period->name_en = $data->nameEn;
        $period->name_ar = $data->nameAr;
        $period->sequence = $data->sequence;
        $period->starts_at = $data->startsAt;
        $period->ends_at = $data->endsAt;
        $period->is_active = true;
        $period->save();

        return $period;
    }

    /**
     * Check for overlap against active periods in the same institution semester.
     *
     * Overlap exists when: candidate.starts_at < sibling.ends_at AND candidate.ends_at > sibling.starts_at.
     * Adjacent boundaries (candidate.ends_at == sibling.starts_at) are explicitly permitted.
     */
    private function assertNoOverlap(int $institutionSemesterId, string $startsAt, string $endsAt, ?int $excludeId = null): void
    {
        $query = OperationalPeriod::where('institution_semester_id', $institutionSemesterId)
            ->where('is_active', true)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new RuntimeException(
                "The period {$startsAt}–{$endsAt} overlaps with an existing active period."
            );
        }
    }
}
