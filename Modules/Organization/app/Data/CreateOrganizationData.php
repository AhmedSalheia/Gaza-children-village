<?php

declare(strict_types=1);

namespace Modules\Organization\Data;

/**
 * Command data for creating a new organization.
 *
 * Stable codes are set at creation only; they cannot be changed through
 * any subsequent name or lifecycle action.
 */
final readonly class CreateOrganizationData
{
    public function __construct(
        public readonly string $code,
        public readonly string $nameEn,
        public readonly ?string $nameAr = null,
    ) {}
}
