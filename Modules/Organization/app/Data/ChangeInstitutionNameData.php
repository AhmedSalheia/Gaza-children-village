<?php

declare(strict_types=1);

namespace Modules\Organization\Data;

/**
 * Command data for changing an institution's display names.
 *
 * Only display names may be changed. The stable code is immutable and is
 * never included in this data object.
 */
final readonly class ChangeInstitutionNameData
{
    public function __construct(
        public readonly string $nameEn,
        public readonly ?string $nameAr = null,
    ) {}
}
