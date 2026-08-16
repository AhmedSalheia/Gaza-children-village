<?php

declare(strict_types=1);

namespace Modules\Workflow\Providers;

use Modules\Audit\Contracts\AuditRecorder;
use Modules\Workflow\Services\ElectronicApprovalService;
use Modules\Workflow\Services\ReconfirmationTokenService;
use Modules\Workflow\Services\WorkflowTransitionService;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class WorkflowServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Workflow';

    protected string $nameLower = 'workflow';

    public function register(): void
    {
        parent::register();

        $this->app->bind(ReconfirmationTokenService::class);
        $this->app->bind(WorkflowTransitionService::class);

        // ElectronicApprovalService depends on ReconfirmationTokenService
        $this->app->bind(ElectronicApprovalService::class, function ($app): ElectronicApprovalService {
            return new ElectronicApprovalService(
                auditRecorder: $app->make(AuditRecorder::class),
                tokenService: $app->make(ReconfirmationTokenService::class),
            );
        });
    }
}
