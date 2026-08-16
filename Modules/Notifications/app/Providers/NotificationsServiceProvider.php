<?php

declare(strict_types=1);

namespace Modules\Notifications\Providers;

use Modules\Notifications\Services\NotificationRouteResolver;
use Modules\Notifications\Services\NotificationService;
use Modules\Notifications\Services\OperationStatusService;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notifications';

    protected string $nameLower = 'notifications';

    public function register(): void
    {
        parent::register();

        $this->app->bind(NotificationRouteResolver::class);
        $this->app->bind(NotificationService::class);
        $this->app->bind(OperationStatusService::class);
    }
}
