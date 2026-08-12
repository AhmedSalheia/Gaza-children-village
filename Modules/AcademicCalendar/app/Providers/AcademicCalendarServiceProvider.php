<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class AcademicCalendarServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AcademicCalendar';

    protected string $nameLower = 'academic-calendar';
}
