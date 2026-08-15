<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

enum EvidenceStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Received = 'received';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
