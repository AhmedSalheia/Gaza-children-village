<?php

declare(strict_types=1);

namespace Modules\Accounts\Providers;

use Illuminate\Support\Facades\Auth;
use Modules\Accounts\Contracts\ChallengeDelivery;
use Modules\Accounts\Services\NullChallengeDelivery;
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

        // Register the default challenge delivery binding.
        // Tests swap this with FakeChallengeDelivery via $this->app->instance().
        // A real SMS/email implementation replaces this binding once a delivery
        // channel is configured and approved.
        $this->app->bind(ChallengeDelivery::class, NullChallengeDelivery::class);
    }
}
