<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for CreateSemester.
 *
 * Dates are expected as ISO 8601 date strings (YYYY-MM-DD).
 * sequence must be a positive integer; enforced in the action.
 * Date-order, containment, and overlap validations are enforced in the action.
 */
final readonly class CreateSemesterData
{
    public function __construct(
        public string $code,
        public string $nameEn,
        public ?string $nameAr = null,
        public int $sequence = 1,
        public string $startsOn = '',
        public string $endsOn = '',
    ) {}
}
