<?php

declare(strict_types=1);

namespace Modules\People\Enums;

enum BirthDatePrecision: string
{
    case Exact = 'exact';
    case Month = 'month';
    case Year = 'year';
    case Unknown = 'unknown';
}
