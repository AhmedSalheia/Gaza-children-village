<?php

declare(strict_types=1);

namespace Modules\Imports\Enums;

/**
 * Domain entity type created or updated by an applied import row.
 */
enum ImportEntityType: string
{
    case Person = 'person';

    case StudentProfile = 'student_profile';

    case Enrollment = 'enrollment';
}
