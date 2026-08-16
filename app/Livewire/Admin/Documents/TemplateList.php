<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Documents;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;
use Modules\Documents\Services\DocumentTypeRegistry;

/**
 * Admin portal: document template list.
 *
 * Organization scoping:
 *   The admin account schema does not carry an organization_id (admin accounts
 *   are global). The organization scope is derived on every render from the
 *   system's registered organization record (code = 'gcv') — a trusted DB
 *   source that cannot be overwritten by a forged Livewire message.
 *
 *   The system organization ID is NOT stored as a Livewire property (neither
 *   public nor private). Livewire re-instantiates and re-hydrates components
 *   on every request; private PHP properties do not survive hydration, so
 *   storing the scope there would reset it to 0 after the initial mount,
 *   causing the template list to appear empty on subsequent renders (e.g.
 *   after a typeFilter change). The org is instead re-queried on each render
 *   via `systemOrganizationId()`.
 *
 *   In a future multi-organization deployment, the admin account schema would
 *   need an org membership edge; replace the `gcv` lookup with a per-account
 *   derivation.
 *
 * Access gated on TEMPLATE_READ permission.
 */
final class TemplateList extends Component
{
    use HasAdminAuth;

    public string $typeFilter = '';

    public string $flashMessage = '';

    /** @var string[] */
    public array $errors = [];

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::TEMPLATE_READ);
    }

    public function render(): View
    {
        $registry = app(DocumentTypeRegistry::class);

        // Derive org scope on every render from the DB (trusted, not cached in
        // a Livewire property). This ensures the scope is correct after hydration
        // and cannot be manipulated by a forged Livewire property update.
        $orgId = $this->systemOrganizationId();

        $templates = $orgId > 0
            ? DB::table('document_templates as dt')
                ->leftJoin('document_template_versions as dtv', 'dtv.id', '=', 'dt.active_version_id')
                ->leftJoin('institutions as i', 'i.id', '=', 'dt.institution_id')
                // Scope: templates for this organization only.
                // Includes org-wide records (organization_id = system org) and
                // institution-specific records where that institution belongs to
                // the system org.
                ->where(function ($q) use ($orgId): void {
                    $q->where('dt.organization_id', $orgId)
                        ->orWhere(function ($q2) use ($orgId): void {
                            $q2->whereIn(
                                'dt.institution_id',
                                DB::table('institutions')
                                    ->where('organization_id', $orgId)
                                    ->select('id'),
                            );
                        });
                })
                ->when($this->typeFilter !== '', fn ($q) => $q->where('dt.document_type_code', $this->typeFilter))
                ->select(
                    'dt.id',
                    'dt.document_type_code',
                    'dt.organization_id',
                    'dt.institution_id',
                    'i.name_ar as institution_name_ar',
                    'dt.approval_required',
                    'dt.ar_available',
                    'dt.en_available',
                    'dt.active_version_id',
                    'dtv.version_number as active_version_number',
                    'dtv.status as active_version_status',
                    'dtv.locale as active_version_locale',
                )
                ->orderBy('dt.document_type_code')
                ->orderBy('dt.institution_id')
                ->get()
            : collect();

        return view('livewire.admin.documents.template-list', [
            'templates' => $templates,
            'typeOptions' => $registry->all(),
            'canManage' => $this->adminCan(PermissionKey::TEMPLATE_MANAGE),
            'canActivate' => $this->adminCan(PermissionKey::TEMPLATE_ACTIVATE),
        ])->layout('layouts.admin');
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    /**
     * Derive the authorized organization from the system DB on every call.
     *
     * Not stored as a Livewire property (public or private) because:
     *   - Public properties are forgeable via Livewire message injection.
     *   - Private PHP properties do not survive Livewire hydration; they reset
     *     to their default value on every request after mount, causing empty
     *     lists when the filter changes.
     */
    private function systemOrganizationId(): int
    {
        return (int) DB::table('organizations')
            ->where('code', 'gcv')
            ->value('id');
    }
}
