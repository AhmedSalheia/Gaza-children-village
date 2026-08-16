<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Documents;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;
use Modules\Documents\Exceptions\TemplateActivationException;
use Modules\Documents\Exceptions\UnknownPlaceholderException;
use Modules\Documents\Models\DocumentTemplate;
use Modules\Documents\Models\DocumentTemplateVersion;
use Modules\Documents\Services\DocumentTemplateVersionService;

/**
 * Admin portal: document template version detail — view, preview, create draft, activate.
 *
 * Organization scoping:
 *   The admin account schema does not carry an organization_id (admin accounts
 *   are global). The expected organization is derived on every action from the
 *   organizations table (code = 'gcv') — a trusted server-side source.
 *
 *   IMPORTANT: The expected organization is NOT stored as a Livewire property.
 *   Livewire public properties can be overwritten by a forged frontend message,
 *   so storing the scope there would allow a legitimate-but-limited user to
 *   manipulate the expectedOrganizationId and gain access to another org's
 *   templates. Instead, `loadScopedTemplate()` always re-derives the authorized
 *   organization from the DB at call time.
 *
 *   In a future multi-organization deployment, the admin account schema would
 *   need an org membership edge; the derivation in `systemOrganizationId()`
 *   would change to a per-account lookup rather than a single code lookup.
 *
 * IDOR protection:
 *   Version actions are scoped to `template_id = $this->templateId` so a forged
 *   version ID belonging to a different template also yields 404.
 *
 * Access gated on TEMPLATE_READ; mutating actions additionally gate on
 * TEMPLATE_MANAGE (create draft) and TEMPLATE_ACTIVATE (activate).
 */
final class TemplateVersionDetail extends Component
{
    use HasAdminAuth;

    public int $templateId;

    // Create-draft form fields
    public bool $showDraftForm = false;

    public string $draftLocale = 'ar';

    public string $draftBody = '';

    public string $draftHeaderHtml = '';

    public string $draftFooterHtml = '';

    // Preview state
    public ?int $previewVersionId = null;

    public ?string $previewHtml = null;

    /** @var string[] */
    public array $errors = [];

    public ?string $flashMessage = null;

    public function mount(int $templateId): void
    {
        $this->requirePermission(PermissionKey::TEMPLATE_READ);
        $this->templateId = $templateId;

        // Eagerly verify the template belongs to the authorized organization
        // (prevents a 404 from confusingly passing mount when the org lookup fails).
        $this->loadScopedTemplate();
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    public function createDraft(): void
    {
        $this->errors = [];

        if (! $this->adminCan(PermissionKey::TEMPLATE_MANAGE)) {
            abort(403);
        }

        if (trim($this->draftBody) === '') {
            $this->errors[] = __('documents.error_body_required', [], null, 'Template body cannot be empty.');

            return;
        }

        $template = $this->loadScopedTemplate();

        try {
            app(DocumentTemplateVersionService::class)->createDraft(
                template: $template,
                locale: $this->draftLocale,
                body: $this->draftBody,
                headerConfig: $this->draftHeaderHtml !== '' ? ['html' => $this->draftHeaderHtml] : null,
                footerConfig: $this->draftFooterHtml !== '' ? ['html' => $this->draftFooterHtml] : null,
                creatorAccountId: $this->adminId(),
            );

            $this->showDraftForm = false;
            $this->draftBody = '';
            $this->draftHeaderHtml = '';
            $this->draftFooterHtml = '';
            $this->flashMessage = __('documents.draft_created', [], null, 'Draft version created successfully.');
        } catch (UnknownPlaceholderException $e) {
            $this->errors[] = $e->getMessage();
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function activate(int $versionId): void
    {
        $this->errors = [];

        if (! $this->adminCan(PermissionKey::TEMPLATE_ACTIVATE)) {
            abort(403);
        }

        // Re-derive org scope from the DB on every action.
        $this->loadScopedTemplate();

        $version = DocumentTemplateVersion::where('template_id', $this->templateId)
            ->findOrFail($versionId);

        try {
            app(DocumentTemplateVersionService::class)->activate(
                version: $version,
                actorAccountId: $this->adminId(),
            );

            $this->flashMessage = __('documents.version_activated', [], null, 'Template version activated successfully.');
        } catch (TemplateActivationException $e) {
            $this->errors[] = $e->getMessage();
        } catch (UnknownPlaceholderException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function previewVersion(int $versionId): void
    {
        $this->errors = [];

        $this->loadScopedTemplate();

        $version = DocumentTemplateVersion::where('template_id', $this->templateId)
            ->findOrFail($versionId);

        try {
            $this->previewHtml = app(DocumentTemplateVersionService::class)->renderPreviewHtml($version);
            $this->previewVersionId = $versionId;
        } catch (UnknownPlaceholderException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function closePreview(): void
    {
        $this->previewHtml = null;
        $this->previewVersionId = null;
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render(): View
    {
        $template = $this->loadScopedTemplate();
        $versions = DocumentTemplateVersion::where('template_id', $this->templateId)
            ->orderByDesc('version_number')
            ->get();

        return view('livewire.admin.documents.template-version-detail', [
            'template' => $template,
            'versions' => $versions,
            'canManage' => $this->adminCan(PermissionKey::TEMPLATE_MANAGE),
            'canActivate' => $this->adminCan(PermissionKey::TEMPLATE_ACTIVATE),
        ])->layout('layouts.admin');
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    /**
     * Derive the authorized organization from the system DB (trusted source).
     *
     * This is intentionally NOT stored as a Livewire public property to prevent
     * forged Livewire messages from overwriting it. It re-queries the DB every
     * time so that stale or injected state cannot persist across requests.
     */
    private function systemOrganizationId(): int
    {
        return (int) DB::table('organizations')
            ->where('code', 'gcv')
            ->value('id');
    }

    /**
     * Load the template and verify it belongs to the authorized organization.
     *
     * The organization is derived fresh from the DB on every call (not from any
     * Livewire property) so forged property mutations cannot bypass this guard.
     * Aborts with 403 if the template's organization does not match.
     */
    private function loadScopedTemplate(): DocumentTemplate
    {
        $authorizedOrgId = $this->systemOrganizationId();

        if ($authorizedOrgId === 0) {
            abort(404, 'System organization not found.');
        }

        $template = DocumentTemplate::findOrFail($this->templateId);

        // Resolve the template's org: use organization_id directly if set,
        // otherwise look up the institution's parent organization.
        $templateOrgId = $template->organization_id
            ?? (int) DB::table('institutions')
                ->where('id', $template->institution_id)
                ->value('organization_id');

        if ($templateOrgId !== $authorizedOrgId) {
            abort(403, 'Access denied: template does not belong to your organization.');
        }

        return $template;
    }
}
