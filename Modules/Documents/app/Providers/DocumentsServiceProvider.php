<?php

declare(strict_types=1);

namespace Modules\Documents\Providers;

use Modules\Documents\Contracts\PdfEngineContract;
use Modules\Documents\Services\DocumentNumberService;
use Modules\Documents\Services\DocumentTemplateVersionService;
use Modules\Documents\Services\DocumentTypeRegistry;
use Modules\Documents\Services\MpdfEngine;
use Modules\Documents\Services\TemplatePlaceholderResolver;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class DocumentsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Documents';

    protected string $nameLower = 'documents';

    public function register(): void
    {
        parent::register();

        // Bind the PDF engine contract to the mPDF implementation.
        // Swap this binding to use a different engine without touching callers.
        $this->app->bind(PdfEngineContract::class, MpdfEngine::class);

        $this->app->bind(TemplatePlaceholderResolver::class);
        $this->app->bind(DocumentNumberService::class);
        $this->app->singleton(DocumentTypeRegistry::class);

        $this->app->bind(DocumentTemplateVersionService::class, function ($app): DocumentTemplateVersionService {
            return new DocumentTemplateVersionService(
                auditRecorder: $app->make('Modules\\Audit\\Contracts\\AuditRecorder'),
                pdfEngine: $app->make(PdfEngineContract::class),
                placeholderResolver: $app->make(TemplatePlaceholderResolver::class),
            );
        });
    }
}
