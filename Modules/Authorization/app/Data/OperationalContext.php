<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

use InvalidArgumentException;

final readonly class OperationalContext
{
    public function __construct(
        public Portal $portal,
        public ActorReference $actor,
        public AuthorizedOperationalScope $scope,
    ) {
        if ($this->actor->portal !== $this->portal) {
            throw new InvalidArgumentException('The actor cannot be reused in a different portal context.');
        }
    }
}
