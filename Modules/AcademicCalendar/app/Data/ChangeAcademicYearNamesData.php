<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for ChangeAcademicYearNames.
 *
 * Name changes are permitted while the academic year is not archived.
 * The stable code is not included; it cannot change through ordinary actions.
 */
final readonly class ChangeAcademicYearNamesData
{
    public function __construct(
        public string $nameEn,
        public ?string $nameAr = null,
    ) {}
}
