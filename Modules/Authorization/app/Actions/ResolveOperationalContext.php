<?php

declare(strict_types=1);

namespace Modules\Authorization\Actions;

use InvalidArgumentException;
use Modules\Authorization\Contracts\OperationalScopeAuthorizer;
use Modules\Authorization\Data\ActorReference;
use Modules\Authorization\Data\AuthorizedOperationalScope;
use Modules\Authorization\Data\OperationalContext;
use Modules\Authorization\Data\Portal;
use Modules\Authorization\Data\ScopeRequirement;
use Modules\Authorization\Data\UntrustedOperationalScope;
use RuntimeException;

final readonly class ResolveOperationalContext
{
    public function __construct(
        private OperationalScopeAuthorizer $authorizer,
    ) {}

    public function execute(
        Portal $portal,
        ActorReference $actor,
        UntrustedOperationalScope $untrustedScope,
        ScopeRequirement $requirement,
    ): OperationalContext {
        if ($actor->portal !== $portal) {
            throw new InvalidArgumentException('The actor cannot be resolved for a different portal.');
        }

        $this->assertUntrustedScopeShape($untrustedScope);

        $authorizedScope = AuthorizedOperationalScope::resolve(
            $portal,
            $actor,
            $untrustedScope,
            $this->authorizer,
        );

        if (! $requirement->isSatisfiedBy($authorizedScope)) {
            throw new RuntimeException("The required {$requirement->value} scope could not be authorized.");
        }

        return new OperationalContext($portal, $actor, $authorizedScope);
    }

    private function assertUntrustedScopeShape(UntrustedOperationalScope $scope): void
    {
        $this->assertReferenceIsUsable($scope->institutionReference, 'institution');
        $this->assertReferenceIsUsable($scope->institutionSemesterReference, 'institution semester');
        $this->assertReferenceIsUsable($scope->operationalPeriodReference, 'operational period');

        if ($scope->institutionSemesterReference !== null && $scope->institutionReference === null) {
            throw new InvalidArgumentException('An institution-semester candidate requires an institution candidate.');
        }

        if ($scope->operationalPeriodReference !== null && $scope->institutionSemesterReference === null) {
            throw new InvalidArgumentException('An operational-period candidate requires an institution-semester candidate.');
        }
    }

    private function assertReferenceIsUsable(?string $reference, string $label): void
    {
        if ($reference !== null && trim($reference) === '') {
            throw new InvalidArgumentException("The untrusted {$label} reference must be non-empty or explicitly absent.");
        }
    }
}
