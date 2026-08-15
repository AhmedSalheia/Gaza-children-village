<?php

declare(strict_types=1);

namespace Modules\Attendance\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class AttendanceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Attendance';

    protected string $nameLower = 'attendance';
}
