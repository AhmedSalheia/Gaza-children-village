<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for ChangeSemesterDates.
 *
 * Structural changes (dates and sequence) are permitted only while the
 * semester is draft. After opening, corrections require a future controlled
 * workflow outside F07 scope.
 *
 * The action validates:
 *   - starts_on < ends_on
 *   - dates fall within the parent academic year dates
 *   - no overlap with sibling semesters in the same year
 *   - sequence is positive and unique within the year
 *
 * Dates are expected as ISO 8601 date strings (YYYY-MM-DD).
 */
final readonly class ChangeSemesterDatesData
{
    public function __construct(
        public int $sequence,
        public string $startsOn,
        public string $endsOn,
    ) {}
}
