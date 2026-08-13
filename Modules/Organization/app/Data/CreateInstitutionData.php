<?php

declare(strict_types=1);

namespace Modules\Organization\Data;

/**
 * Command data for creating a new institution.
 *
 * Stable codes are set at creation only; they cannot be changed through
 * any subsequent name or lifecycle action.
 */
final readonly class CreateInstitutionData
{
    public function __construct(
        public readonly string $code,
        public readonly int $organizationId,
        public readonly int $institutionTypeId,
        public readonly string $nameEn,
        public readonly ?string $nameAr = null,
    ) {}
}
