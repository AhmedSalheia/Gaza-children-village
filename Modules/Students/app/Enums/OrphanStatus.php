<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

enum OrphanStatus: string
{
    case NotOrphan = 'not_orphan';
    case SingleOrphan = 'single_orphan';
    case DoubleOrphan = 'double_orphan';
    case Unknown = 'unknown';
}
