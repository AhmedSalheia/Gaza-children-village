<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\Institution;

/**
 * Activates an institution.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class ActivateInstitution
{
    public function execute(Institution $institution): Institution
    {
        $institution->is_active = true;
        $institution->save();

        return $institution;
    }
}
