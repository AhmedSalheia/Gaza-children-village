<?php

declare(strict_types=1);

namespace Modules\Organization\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class OrganizationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Organization';

    protected string $nameLower = 'organization';
}
