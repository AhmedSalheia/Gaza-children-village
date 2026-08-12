<?php

declare(strict_types=1);

namespace Modules\People\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class PeopleServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'People';

    protected string $nameLower = 'people';
}
