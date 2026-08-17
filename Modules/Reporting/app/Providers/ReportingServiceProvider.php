<?php

declare(strict_types=1);

namespace Modules\Reporting\Providers;

use Modules\Reporting\Services\FormulaInjectionSanitizer;
use Modules\Reporting\Services\ReportQueryService;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class ReportingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Reporting';

    protected string $nameLower = 'reporting';

    public function register(): void
    {
        parent::register();

        $this->app->bind(FormulaInjectionSanitizer::class);
        $this->app->singleton(ReportQueryService::class);
    }
}
