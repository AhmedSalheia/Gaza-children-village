<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Audit log for civil registry lookups.
 *
 * Records are written to audit_events (not a separate civil_registry_audit_log table).
 * The CivilRegistryLookupService records one event per lookup with:
 *   source_module = 'CivilRegistry'
 *   action        = 'civil_registry.lookup'
 *   actor_type    = 'staff' (or 'administrative')
 *   metadata      = { lookup_correlation: string, found: bool }
 *
 * No national IDs are stored — only actor, institution, and the correlation key.
 */
final class CivilRegistryAudit extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public int $institutionFilter = 0;

    #[Url]
    public string $actorTypeFilter = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount(): void
    {
        $this->requirePermission('audit.view');
    }

    public function updatingInstitutionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingActorTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function auditEntries(): LengthAwarePaginator
    {
        return DB::table('audit_events as ae')
            ->leftJoin('institutions as i', 'i.id', '=', 'ae.institution_id')
            ->select([
                'ae.id',
                'ae.actor_type',
                'ae.actor_account_id',
                'ae.institution_id',
                'ae.metadata',
                'ae.recorded_at',
                'i.name_ar as institution_name',
            ])
            ->where('ae.source_module', 'CivilRegistry')
            ->where('ae.action', 'civil_registry.lookup')
            ->when($this->institutionFilter > 0, fn ($q) => $q->where('ae.institution_id', $this->institutionFilter))
            ->when($this->actorTypeFilter !== '', fn ($q) => $q->where('ae.actor_type', $this->actorTypeFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->where('ae.recorded_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->where('ae.recorded_at', '<=', $this->dateTo.' 23:59:59'))
            ->orderByDesc('ae.recorded_at')
            ->paginate(30);
    }

    public function institutions(): Collection
    {
        return DB::table('institutions')->where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar']);
    }

    public function render(): View
    {
        return view('livewire.admin.audit.civil-registry', [
            'auditEntries' => $this->auditEntries(),
            'institutions' => $this->institutions(),
        ])->layout('layouts.admin');
    }
}
