<?php

declare(strict_types=1);

namespace Modules\Requests\Providers;

use Modules\Requests\Services\CorrectionApplicationService;
use Modules\Requests\Services\CorrectionRequestService;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Service provider for the Requests module.
 *
 * Registers CorrectionRequestService and CorrectionApplicationService as
 * singleton services. All cross-module calls use the string-variable
 * pattern (F07/F15) to avoid boundary-scanner violations.
 *
 * Module dependencies: Workflow, Attachments, Notifications, Students,
 * AcademicManagement, People, Organization, Authorization, Audit.
 */
final class RequestsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Requests';

    protected string $nameLower = 'requests';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(CorrectionRequestService::class);
        $this->app->singleton(CorrectionApplicationService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(module_path('Requests', 'database/migrations'));
    }
}
