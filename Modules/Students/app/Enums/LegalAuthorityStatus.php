<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

enum LegalAuthorityStatus: string
{
    case Full = 'full';
    case Limited = 'limited';
    case None = 'none';
    case Unknown = 'unknown';
}
