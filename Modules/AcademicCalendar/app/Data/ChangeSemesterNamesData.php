<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for ChangeSemesterNames.
 *
 * Name changes are permitted while the semester is not archived and its
 * parent academic year is not archived.
 *
 * The stable code is not included; it cannot change through ordinary actions.
 */
final readonly class ChangeSemesterNamesData
{
    public function __construct(
        public string $nameEn,
        public ?string $nameAr = null,
    ) {}
}
