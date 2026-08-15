<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

enum DisplacementStatus: string
{
    case Resident = 'resident';
    case InternallyDisplaced = 'internally_displaced';
    case ExternallyDisplaced = 'externally_displaced';
    case Returned = 'returned';
    case Unknown = 'unknown';
}
