<?php

declare(strict_types=1);

namespace Modules\Students\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class StudentsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Students';

    protected string $nameLower = 'students';
}
