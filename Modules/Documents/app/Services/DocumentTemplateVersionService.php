<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Documents\Contracts\PdfEngineContract;
use Modules\Documents\Data\DocumentDataContext;
use Modules\Documents\Exceptions\TemplateActivationException;
use Modules\Documents\Models\DocumentTemplate;
use Modules\Documents\Models\DocumentTemplateVersion;

/**
 * Manages document template versioning, activation, and preview rendering.
 *
 * Versioning invariants enforced here:
 *   1. Creating a new version increments version_number (never reuses).
 *   2. Only draft versions may have their body edited (assertIsDraft guard on model).
 *   3. Activation validates all placeholders, computes content_hash, sets status='active',
 *      archives the previously active version, and updates template.active_version_id.
 *   4. Every activation writes an audit event via AuditRecorder.
 *   5. Archived and active versions are read-only — any edit attempt throws LogicException.
 */
final class DocumentTemplateVersionService
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
        private readonly PdfEngineContract $pdfEngine,
        private readonly TemplatePlaceholderResolver $placeholderResolver,
    ) {}

    /**
     * Create a new draft version for the given template.
     *
     * The version_number is one greater than the current highest version
     * for this template (or 1 if no versions exist).
     */
    public function createDraft(
        DocumentTemplate $template,
        string $locale,
        string $body,
        ?array $headerConfig = null,
        ?array $footerConfig = null,
        ?string $effectiveFrom = null,
        ?string $effectiveTo = null,
        ?int $creatorAccountId = null,
    ): DocumentTemplateVersion {
        // Sanitize HTML (strip dangerous tags/attributes) before placeholder validation
        // so the closed catalogue check runs on the clean body.
        $body = $this->placeholderResolver->sanitizeHtml($body);

        // Sanitize the embedded 'html' strings inside the config arrays (if provided).
        if ($headerConfig !== null && isset($headerConfig['html'])) {
            $headerConfig['html'] = $this->placeholderResolver->sanitizeHtml((string) $headerConfig['html']);
        }

        if ($footerConfig !== null && isset($footerConfig['html'])) {
            $footerConfig['html'] = $this->placeholderResolver->sanitizeHtml((string) $footerConfig['html']);
        }

        // Validate placeholders before persisting the draft.
        $this->placeholderResolver->validateBody($body);

        $catalogue = $this->placeholderResolver->extractPlaceholders($body);

        // Version-number allocation and INSERT must happen in one transaction.
        //
        // Invariant: (template_id, version_number) is unique; version numbers must
        // be monotonically increasing and must never be reused.
        //
        // Race condition without a lock: two simultaneous createDraft() calls both
        // read max(version_number) = N, both compute N+1, and both attempt to INSERT
        // with version_number = N+1. One succeeds; the other fails on the unique
        // constraint. Giving the next number inside the same transaction that holds
        // the lock on the template row prevents this — the second caller blocks until
        // the first transaction (lock + INSERT) commits, then reads the updated max.
        return DB::transaction(function () use (
            $template,
            $locale,
            $body,
            $catalogue,
            $headerConfig,
            $footerConfig,
            $effectiveFrom,
            $effectiveTo,
            $creatorAccountId,
        ): DocumentTemplateVersion {
            // Lock the template row. The second concurrent call blocks here until
            // the first transaction commits and releases the lock.
            DocumentTemplate::lockForUpdate()->findOrFail($template->id);

            // Re-read max inside the lock so we see any version that committed
            // since we entered this method.
            $nextVersion = ($template->versions()->max('version_number') ?? 0) + 1;

            $version = new DocumentTemplateVersion;
            $version->template_id = $template->id;
            $version->version_number = $nextVersion;
            $version->locale = $locale;
            $version->body = $body;
            $version->placeholder_catalogue = $catalogue;
            $version->header_config = $headerConfig;
            $version->footer_config = $footerConfig;
            $version->effective_from = $effectiveFrom;
            $version->effective_to = $effectiveTo;
            $version->status = 'draft';
            $version->creator_account_id = $creatorAccountId;
            $version->content_hash = null; // set on activation
            $version->save();

            return $version;
        });
    }

    /**
     * Activate a draft template version.
     *
     * Steps (all inside one serializing DB transaction):
     *   1. Lock the template row to serialize concurrent activations.
     *   2. Lock and reload the candidate version by ID (lockForUpdate).
     *      This prevents the stale-model race: a request that read a draft
     *      before another activation archived it must see the updated status
     *      inside its own transaction — not the caller's stale model.
     *   3. Assert the freshly loaded version is still a draft (inside the lock).
     *   4. Re-validate placeholders on the locked body.
     *   5. Compute and store content_hash.
     *   6. Transition this version: draft → active.
     *   7. Archive the previously active version (if any): active → archived.
     *   8. Update template.active_version_id.
     *   9. Write an audit event.
     *
     * @param  int  $actorAccountId  Admin account performing the activation
     *
     * @throws TemplateActivationException When version is not a draft (evaluated
     *                                     inside the transaction on the locked row)
     */
    public function activate(
        DocumentTemplateVersion $version,
        int $actorAccountId,
    ): DocumentTemplateVersion {
        $versionId = $version->id;
        $templateId = $version->template_id;

        return DB::transaction(function () use ($versionId, $templateId, $actorAccountId): DocumentTemplateVersion {
            // Lock the template first to establish a total ordering across concurrent
            // activations on the same template.
            $template = DocumentTemplate::lockForUpdate()->findOrFail($templateId);

            // Lock and reload the candidate version. This is the critical fix:
            // the caller's $version may be stale (read before a concurrent activation
            // archived it). We must load and assert the status inside the lock, not
            // before it, so the assertion cannot be defeated by a TOCTOU window.
            $lockedVersion = DocumentTemplateVersion::lockForUpdate()->findOrFail($versionId);

            if (! $lockedVersion->isDraft()) {
                throw new TemplateActivationException(
                    "Version #{$lockedVersion->id} (v{$lockedVersion->version_number}, status='{$lockedVersion->status}') is not a draft and cannot be activated."
                );
            }

            // Re-validate placeholders on the authoritative body (from the locked row).
            $this->placeholderResolver->validateBody($lockedVersion->body);

            // Archive the currently active version (if any)
            if ($template->active_version_id !== null && $template->active_version_id !== $lockedVersion->id) {
                DocumentTemplateVersion::where('id', $template->active_version_id)
                    ->update(['status' => 'archived']);
            }

            // Transition this version: draft → active
            $lockedVersion->status = 'active';
            $lockedVersion->content_hash = $this->placeholderResolver->contentHash($lockedVersion->body);
            $lockedVersion->approver_account_id = $actorAccountId;
            $lockedVersion->save();

            // Update template pointer
            $template->active_version_id = $lockedVersion->id;
            $template->save();

            // Write audit event
            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'administrative',
                sourceModule: 'Documents',
                action: 'document_template.activated',
                actorAccountId: $actorAccountId,
                subjectType: 'DocumentTemplateVersion',
                subjectId: $lockedVersion->id,
                afterState: [
                    'template_id' => $lockedVersion->template_id,
                    'version_number' => $lockedVersion->version_number,
                    'locale' => $lockedVersion->locale,
                    'integrity_digest' => $lockedVersion->content_hash,
                ],
            ));

            return $lockedVersion->fresh();
        });
    }

    /**
     * Render a template version body to HTML using synthetic or real data.
     *
     * Used by the admin preview endpoint. Never issues real document numbers
     * or touches any student record.
     *
     * @param  DocumentDataContext|null  $context  Null → synthetic sample data
     */
    public function renderPreviewHtml(
        DocumentTemplateVersion $version,
        ?DocumentDataContext $context = null,
    ): string {
        $ctx = $context ?? DocumentDataContext::synthetic(
            documentTypeLabelAr: 'معاينة',
            documentTypeLabelEn: 'Preview',
        );

        // Re-sanitize at render time as a defence-in-depth layer for versions that
        // pre-date the storage-layer sanitization guard.
        $body = $this->placeholderResolver->sanitizeHtml($version->body);

        return $this->placeholderResolver->resolve($body, $ctx);
    }

    /**
     * Generate a preview PDF for a template version.
     *
     * @param  DocumentDataContext|null  $context  Null → synthetic sample data
     */
    public function renderPreviewPdf(
        DocumentTemplateVersion $version,
        ?DocumentDataContext $context = null,
    ): string {
        $html = $this->renderPreviewHtml($version, $context);

        // Re-sanitize header/footer at render time as a defence-in-depth layer.
        // createDraft() sanitizes them at storage time, but records that pre-date
        // the storage-layer sanitization guard (or that were inserted directly)
        // could contain unsafe content. Sanitizing again here closes that gap.
        $headerHtml = isset($version->header_config['html'])
            ? $this->placeholderResolver->sanitizeHtml((string) $version->header_config['html'])
            : null;

        $footerHtml = isset($version->footer_config['html'])
            ? $this->placeholderResolver->sanitizeHtml((string) $version->footer_config['html'])
            : null;

        return $this->pdfEngine->generateFromHtml($html, [
            'direction' => $version->locale === 'en' ? 'ltr' : 'rtl',
            'header_html' => $headerHtml,
            'footer_html' => $footerHtml,
        ]);
    }
}
