<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

enum RelationshipType: string
{
    case Father = 'father';
    case Mother = 'mother';
    case LegalGuardian = 'legal_guardian';
    case Grandparent = 'grandparent';
    case Sibling = 'sibling';
    case Relative = 'relative';
    case FosterCarer = 'foster_carer';
    case Representative = 'representative';
    case Other = 'other';
}
