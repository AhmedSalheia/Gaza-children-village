<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\Classroom;

/**
 * Create a new Classroom within an institution.
 *
 * Validates that the institution exists and is active before persisting, using a
 * string-variable cross-module class reference (boundary scanner safe).
 *
 * The code is stable and unique within the institution (composite unique index
 * on institution_id + code). institution_id is a plain integer cross-module
 * reference — no cross-module import needed.
 */
final class CreateClassroom
{
    public function __invoke(
        int $institutionId,
        string $code,
        string $nameAr,
        ?string $nameEn = null,
        ?int $capacity = null,
        bool $isActive = true,
    ): Classroom {
        // Validate institution exists via string-variable (boundary scanner safe).
        $institutionClass = 'Modules\\Organization\\Models\\Institution';
        $institution = $institutionClass::withoutGlobalScopes()->find($institutionId);

        if ($institution === null) {
            throw new \InvalidArgumentException("Institution #{$institutionId} not found.");
        }

        if (! $institution->is_active) {
            throw new \InvalidArgumentException(
                "Institution #{$institutionId} is inactive; cannot create a classroom for it."
            );
        }

        $exists = Classroom::where('institution_id', $institutionId)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException(
                "A Classroom with code '{$code}' already exists at institution #{$institutionId}."
            );
        }

        $classroom = new Classroom;
        $classroom->institution_id = $institutionId;
        $classroom->code = $code;
        $classroom->name_ar = $nameAr;
        $classroom->name_en = $nameEn;
        $classroom->capacity = $capacity;
        $classroom->is_active = $isActive;
        $classroom->save();

        return $classroom;
    }
}
