<?php

declare(strict_types=1);

namespace Modules\Imports\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class ImportsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Imports';

    protected string $nameLower = 'imports';

    public function register(): void
    {
        parent::register();

        // Register module migrations.
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    public function boot(): void
    {
        parent::boot();
    }
}
