<?php

declare(strict_types=1);

namespace Modules\Authorization\Contracts;

use Modules\Authorization\Data\ActorReference;
use Modules\Authorization\Data\Portal;
use Modules\Authorization\Data\ResolvedOperationalScope;
use Modules\Authorization\Data\UntrustedOperationalScope;

interface OperationalScopeAuthorizer
{
    /**
     * Resolve every supplied reference, validate its parent chain, and authorize the actor.
     */
    public function resolveScope(
        Portal $portal,
        ActorReference $actor,
        UntrustedOperationalScope $scope,
    ): ResolvedOperationalScope;
}
