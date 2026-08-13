<?php

declare(strict_types=1);

namespace Modules\Organization\Data;

/**
 * Command data for changing a feature module's display names.
 *
 * Only display names may be changed. The stable code is immutable through
 * this action.
 */
final readonly class ChangeFeatureModuleNameData
{
    public function __construct(
        public readonly string $nameEn,
        public readonly ?string $nameAr = null,
    ) {}
}
