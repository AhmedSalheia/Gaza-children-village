<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

/**
 * Immutable result from the PolicyKernel.
 *
 * Callers check ->allowed first. If false, ->denialReason is always set.
 * If true, denialReason is null.
 */
final class PolicyDecision
{
    private function __construct(
        public readonly bool $allowed,
        public readonly ?DenialReason $denialReason,
        public readonly ?string $denialContext = null,
    ) {}

    public static function allow(): self
    {
        return new self(true, null);
    }

    public static function deny(DenialReason $reason, ?string $context = null): self
    {
        return new self(false, $reason, $context);
    }
}
