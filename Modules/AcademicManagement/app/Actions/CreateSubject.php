<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\Subject;

/**
 * Create a new Subject catalogue entry.
 *
 * The code is a stable globally unique machine identifier (e.g. 'MATH', 'ARABIC').
 */
final class CreateSubject
{
    public function __invoke(
        string $code,
        string $nameAr,
        string $nameEn,
        bool $isActive = true,
    ): Subject {
        if (Subject::where('code', $code)->exists()) {
            throw new \InvalidArgumentException("A Subject with code '{$code}' already exists.");
        }

        $subject = new Subject;
        $subject->code = $code;
        $subject->name_ar = $nameAr;
        $subject->name_en = $nameEn;
        $subject->is_active = $isActive;
        $subject->save();

        return $subject;
    }
}
