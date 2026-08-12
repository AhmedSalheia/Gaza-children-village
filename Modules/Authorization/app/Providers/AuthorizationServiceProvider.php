<?php

declare(strict_types=1);

namespace Modules\Authorization\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class AuthorizationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Authorization';

    protected string $nameLower = 'authorization';
}
