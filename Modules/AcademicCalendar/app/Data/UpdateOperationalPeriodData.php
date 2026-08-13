<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for updating an existing operational period.
 *
 * All fields are nullable; supply only the fields to change. The action
 * applies only the non-null values.
 *
 * Code and sequence are excluded from updates; they are stable identifiers.
 * Use deactivation instead of deletion to remove a period.
 *
 * Only permitted while the parent institution semester is Draft.
 */
final readonly class UpdateOperationalPeriodData
{
    public function __construct(
        public ?string $nameEn = null,
        public ?string $nameAr = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
