<?php

declare(strict_types=1);

namespace Modules\Authorization\Context;

use LogicException;
use Modules\Authorization\Contracts\OperationalContextStore;
use Modules\Authorization\Data\OperationalContext;
use RuntimeException;

final class ScopedOperationalContextStore implements OperationalContextStore
{
    private ?OperationalContext $context = null;

    public function set(OperationalContext $context): void
    {
        if ($this->context !== null) {
            throw new LogicException('The operational context cannot be replaced within one lifecycle.');
        }

        $this->context = $context;
    }

    public function has(): bool
    {
        return $this->context !== null;
    }

    public function current(): OperationalContext
    {
        return $this->context
            ?? throw new RuntimeException('No trusted operational context exists in this lifecycle.');
    }
}
