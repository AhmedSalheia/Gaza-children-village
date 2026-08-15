<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\AcademicLevel;

/**
 * Create a new AcademicLevel catalogue entry.
 *
 * The code is a stable machine identifier (e.g. 'KG1', 'GRADE1') and must be
 * unique. It is excluded from $fillable and assigned directly.
 */
final class CreateAcademicLevel
{
    public function __invoke(
        string $code,
        string $nameAr,
        string $nameEn,
        int $sequence = 0,
        bool $isActive = true,
    ): AcademicLevel {
        if (AcademicLevel::where('code', $code)->exists()) {
            throw new \InvalidArgumentException("An AcademicLevel with code '{$code}' already exists.");
        }

        $level = new AcademicLevel;
        $level->code = $code;
        $level->name_ar = $nameAr;
        $level->name_en = $nameEn;
        $level->sequence = $sequence;
        $level->is_active = $isActive;
        $level->save();

        return $level;
    }
}
