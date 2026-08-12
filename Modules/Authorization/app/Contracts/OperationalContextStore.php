<?php

declare(strict_types=1);

namespace Modules\Authorization\Contracts;

use Modules\Authorization\Data\OperationalContext;

interface OperationalContextStore
{
    public function set(OperationalContext $context): void;

    public function has(): bool;

    public function current(): OperationalContext;
}
