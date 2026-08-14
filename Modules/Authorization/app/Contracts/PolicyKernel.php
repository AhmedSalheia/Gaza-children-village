<?php

declare(strict_types=1);

namespace Modules\Authorization\Contracts;

use Modules\Authorization\Data\AuthorizationDecisionContext;
use Modules\Authorization\Data\PolicyDecision;

/**
 * Public surface of the Authorization policy engine.
 *
 * Resolve a PolicyDecision for a given context. The 9-step deny-precedence
 * chain is documented in ADR F17. Always returns a PolicyDecision — never
 * throws for a normal authorization check.
 */
interface PolicyKernel
{
    public function decide(AuthorizationDecisionContext $context): PolicyDecision;

    /**
     * Helper: return true if the decision allows, false otherwise.
     * Callers that only care about the boolean can use this.
     */
    public function allows(AuthorizationDecisionContext $context): bool;
}
