<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Modules\Audit\Contracts\AuditReader;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Services\DatabaseAuditReader;
use Modules\Audit\Services\DatabaseAuditRecorder;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class AuditServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Audit';

    protected string $nameLower = 'audit';

    public function register(): void
    {
        parent::register();

        $this->app->scoped(AuditRecorder::class, DatabaseAuditRecorder::class);
        $this->app->scoped(AuditReader::class, DatabaseAuditReader::class);
    }
}
