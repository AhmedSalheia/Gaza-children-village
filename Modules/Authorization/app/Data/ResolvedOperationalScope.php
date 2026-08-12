<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

final readonly class ResolvedOperationalScope
{
    public function __construct(
        public ?string $institutionReference,
        public ?string $institutionSemesterReference,
        public ?string $operationalPeriodReference,
    ) {}
}
