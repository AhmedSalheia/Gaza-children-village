<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for CreateAcademicYear.
 *
 * Dates are expected as ISO 8601 date strings (YYYY-MM-DD).
 * Date-order validation (starts_on < ends_on) is enforced in the action.
 */
final readonly class CreateAcademicYearData
{
    public function __construct(
        public string $code,
        public string $nameEn,
        public ?string $nameAr = null,
        public string $startsOn = '',
        public string $endsOn = '',
    ) {}
}
