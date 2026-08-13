<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Data;

/**
 * Input for adding a new operational period to a draft institution semester.
 */
final readonly class CreateOperationalPeriodData
{
    /**
     * @param  string  $code  Stable machine-readable identifier, unique within the institution semester.
     * @param  string  $nameEn  Required English display name.
     * @param  string|null  $nameAr  Optional Arabic display name.
     * @param  int  $sequence  Positive integer ordering within the institution semester.
     * @param  string  $startsAt  Start time in HH:MM or HH:MM:SS format.
     * @param  string  $endsAt  End time in HH:MM or HH:MM:SS format. Must be later than startsAt.
     */
    public function __construct(
        public string $code,
        public string $nameEn,
        public ?string $nameAr,
        public int $sequence,
        public string $startsAt,
        public string $endsAt,
    ) {}
}
