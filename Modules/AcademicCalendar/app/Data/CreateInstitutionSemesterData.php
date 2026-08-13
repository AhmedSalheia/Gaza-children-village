<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for creating a new institution semester.
 *
 * The institution is passed directly to the action as an object; only the
 * global semester ID is captured here to avoid importing the Semester class
 * into the data layer unnecessarily.
 */
final readonly class CreateInstitutionSemesterData
{
    public function __construct(
        public int $semesterId,
        public ?int $copiedFromId = null,
    ) {}
}
