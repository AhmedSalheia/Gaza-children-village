<?php

declare(strict_types=1);

namespace Modules\Accounts\Providers;

use Illuminate\Support\Facades\Auth;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class AccountsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Accounts';

    protected string $nameLower = 'accounts';

    public function boot(): void
    {
        parent::boot();

        // Register the lifecycle-aware Eloquent provider driver.
        // Referenced as 'accounts-eloquent' in config/auth.php for all three portal providers.
        Auth::provider('accounts-eloquent', function ($app, array $config): AccountEloquentUserProvider {
            return new AccountEloquentUserProvider($app['hash'], $config['model']);
        });
    }
}
