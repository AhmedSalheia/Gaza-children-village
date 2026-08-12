<?php

declare(strict_types=1);

namespace Modules\Accounts\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

final class AccountsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Accounts';

    protected string $nameLower = 'accounts';
}
