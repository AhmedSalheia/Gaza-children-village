<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class AuditServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Audit';

    protected string $nameLower = 'audit';
}
