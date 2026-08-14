<?php

declare(strict_types=1);

namespace Modules\People\Enums;

enum ContactPointType: string
{
    case Phone = 'phone';
    case Email = 'email';
}
