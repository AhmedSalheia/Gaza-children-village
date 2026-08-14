<?php

declare(strict_types=1);

namespace Modules\Authorization\Providers;

use Modules\Authorization\Context\ScopedOperationalContextStore;
use Modules\Authorization\Contracts\OperationalContextStore;
use Modules\Authorization\Contracts\PolicyKernel;
use Modules\Authorization\Services\PolicyKernelService;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class AuthorizationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Authorization';

    protected string $nameLower = 'authorization';

    public function register(): void
    {
        parent::register();

        $this->app->scoped(OperationalContextStore::class, ScopedOperationalContextStore::class);
        $this->app->scoped(PolicyKernel::class, PolicyKernelService::class);
    }
}
