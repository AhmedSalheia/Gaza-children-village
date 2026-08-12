<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

use InvalidArgumentException;

final readonly class ActorReference
{
    public function __construct(
        public Portal $portal,
        public ActorCategory $category,
        public ActorSource $source,
        public string $reference,
    ) {
        if (trim($this->reference) === '') {
            throw new InvalidArgumentException('An actor reference must be a non-empty opaque value.');
        }

        if (! $this->category->isCompatibleWith($this->portal)) {
            throw new InvalidArgumentException('The actor category does not belong to the selected portal.');
        }
    }
}
