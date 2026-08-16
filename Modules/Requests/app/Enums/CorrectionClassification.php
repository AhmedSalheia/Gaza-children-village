<?php

declare(strict_types=1);

namespace Modules\Requests\Enums;

/**
 * Classification tiers for correction request fields.
 *
 * Standard fields can be approved by a secretary.
 * Sensitive fields require principal/deputy approval and an ElectronicApproval record.
 */
enum CorrectionClassification: string
{
    case Standard = 'standard';
    case Sensitive = 'sensitive';
}
