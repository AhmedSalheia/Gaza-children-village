<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\InstitutionType;

/**
 * Deactivates an institution type.
 *
 * Deactivation preserves the record and all historical references to it.
 * Records are never deleted. Inactive types remain queryable for institutions
 * that hold historical references to them.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class DeactivateInstitutionType
{
    public function execute(InstitutionType $type): InstitutionType
    {
        $type->is_active = false;
        $type->save();

        return $type;
    }
}
