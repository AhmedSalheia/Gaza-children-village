<?php

declare(strict_types=1);

namespace Modules\Attachments\Providers;

use Modules\Attachments\Contracts\VirusScannerContract;
use Modules\Attachments\Services\SecureAttachmentService;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class AttachmentsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Attachments';

    protected string $nameLower = 'attachments';

    public function register(): void
    {
        parent::register();

        // Bind the optional virus scanner.
        // When config('attachments.scanner') is null, no binding is registered
        // and SecureAttachmentService treats all files as 'quarantine'.
        $scannerClass = config('attachments.scanner');

        if ($scannerClass !== null && class_exists($scannerClass)) {
            $this->app->bind(VirusScannerContract::class, $scannerClass);
        }

        $this->app->bind(SecureAttachmentService::class);
    }
}
