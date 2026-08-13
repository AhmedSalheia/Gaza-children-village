<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\InstitutionType;

/**
 * Activates an institution type.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class ActivateInstitutionType
{
    public function execute(InstitutionType $type): InstitutionType
    {
        $type->is_active = true;
        $type->save();

        return $type;
    }
}
