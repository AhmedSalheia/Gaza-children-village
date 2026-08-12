<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

enum Portal: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Guardian = 'guardian';
}
