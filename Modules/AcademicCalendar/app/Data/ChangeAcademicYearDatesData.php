<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for ChangeAcademicYearDates.
 *
 * Date changes are permitted only while the academic year is draft.
 * After opening, date corrections require a future controlled workflow
 * outside F07 scope.
 *
 * The action re-validates all existing semesters to ensure they remain
 * contained within the new year dates.
 *
 * Dates are expected as ISO 8601 date strings (YYYY-MM-DD).
 */
final readonly class ChangeAcademicYearDatesData
{
    public function __construct(
        public string $startsOn,
        public string $endsOn,
    ) {}
}
