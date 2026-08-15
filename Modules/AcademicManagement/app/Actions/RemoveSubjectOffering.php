<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\InstitutionSubjectOffering;

/**
 * Remove a subject offering from an InstitutionSemester.
 *
 * This deletes the offering record. The subject catalogue entry and any
 * existing teaching assignments referencing this offering must be handled
 * by the caller before removal.
 */
final class RemoveSubjectOffering
{
    public function __invoke(InstitutionSubjectOffering $offering): void
    {
        $offering->delete();
    }
}
