<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

use InvalidArgumentException;
use Modules\Authorization\Contracts\OperationalScopeAuthorizer;
use RuntimeException;

final readonly class AuthorizedOperationalScope
{
    private function __construct(
        public ?string $institutionReference,
        public ?string $institutionSemesterReference,
        public ?string $operationalPeriodReference,
    ) {
        $this->assertReferenceIsUsable($this->institutionReference, 'institution');
        $this->assertReferenceIsUsable($this->institutionSemesterReference, 'institution semester');
        $this->assertReferenceIsUsable($this->operationalPeriodReference, 'operational period');

        if ($this->institutionSemesterReference !== null && $this->institutionReference === null) {
            throw new InvalidArgumentException('An institution-semester scope requires its institution scope.');
        }

        if ($this->operationalPeriodReference !== null && $this->institutionSemesterReference === null) {
            throw new InvalidArgumentException('An operational-period scope requires its institution-semester scope.');
        }
    }

    public static function resolve(
        Portal $portal,
        ActorReference $actor,
        UntrustedOperationalScope $untrustedScope,
        OperationalScopeAuthorizer $authorizer,
    ): self {
        $resolvedScope = $authorizer->resolveScope($portal, $actor, $untrustedScope);

        if (
            $untrustedScope->institutionReference !== $resolvedScope->institutionReference
            || $untrustedScope->institutionSemesterReference !== $resolvedScope->institutionSemesterReference
            || $untrustedScope->operationalPeriodReference !== $resolvedScope->operationalPeriodReference
        ) {
            throw new RuntimeException('The resolved scope does not match the explicitly requested scope chain.');
        }

        return new self(
            $resolvedScope->institutionReference,
            $resolvedScope->institutionSemesterReference,
            $resolvedScope->operationalPeriodReference,
        );
    }

    private function assertReferenceIsUsable(?string $reference, string $label): void
    {
        if ($reference !== null && trim($reference) === '') {
            throw new InvalidArgumentException("The {$label} reference must be non-empty or explicitly absent.");
        }
    }
}
