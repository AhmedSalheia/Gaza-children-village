<?php

declare(strict_types=1);

namespace Modules\Staff\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class StaffServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Staff';

    protected string $nameLower = 'staff';
}
