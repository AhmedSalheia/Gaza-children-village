<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Providers;

use Modules\CivilRegistry\Console\ImportCivilRegistryCommand;
use Modules\CivilRegistry\Contracts\CivilRegistryLookupContract;
use Modules\CivilRegistry\Services\CivilRegistryLookupService;
use Modules\CivilRegistry\Services\NullCivilRegistryLookup;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class CivilRegistryServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'CivilRegistry';

    protected string $nameLower = 'civilregistry';

    public function register(): void
    {
        parent::register();

        // config/civil-registry.php lives in the main config/ directory and is
        // loaded automatically by Laravel — no mergeConfigFrom needed here.

        // Bind the contract to the null implementation when disabled or under test,
        // and to the real service in production with the dataset loaded.
        $this->app->bind(CivilRegistryLookupContract::class, function () {
            if (! config('civil-registry.enabled') || $this->app->runningUnitTests()) {
                return new NullCivilRegistryLookup;
            }

            return new CivilRegistryLookupService;
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCivilRegistryCommand::class,
            ]);
        }
    }
}
