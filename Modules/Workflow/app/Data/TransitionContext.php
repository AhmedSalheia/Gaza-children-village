<?php

declare(strict_types=1);

namespace Modules\Workflow\Data;

/**
 * Value object carrying everything needed to perform one workflow transition.
 *
 * All values are pre-validated / pre-authorized by the caller.
 * WorkflowTransitionService does not re-check authorization; it enforces
 * structural rules only (valid transition, institution scope, immutability).
 *
 * @param  string  $actorType  'administrative'|'staff'|'guardian'|'system'
 * @param  string  $portal  'admin'|'staff'|'guardian'|'system'
 */
final class TransitionContext
{
    public function __construct(
        public readonly string $actorType,
        public readonly string $portal,
        public readonly ?int $actorAccountId = null,
        public readonly ?string $comment = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $metadata = null,
    ) {}
}
