<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Institutions;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\Scopes\ActiveInstitutionScope;

/**
 * Searchable, filterable list of all GCV institutions.
 */
final class InstitutionIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->requirePermission('institution.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function institutions(): LengthAwarePaginator
    {
        return Institution::withoutGlobalScope(ActiveInstitutionScope::class)
            ->with('institutionType')
            ->when($this->search !== '', function ($q): void {
                $s = "%{$this->search}%";
                $q->where(function ($inner) use ($s): void {
                    $inner->where('name_ar', 'like', $s)
                        ->orWhere('name_en', 'like', $s)
                        ->orWhere('code', 'like', $s);
                });
            })
            ->when($this->typeFilter !== '', fn ($q) => $q->where('institution_type_id', $this->typeFilter))
            ->when($this->statusFilter !== 'all', function ($q): void {
                $q->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('name_ar')
            ->paginate(20);
    }

    public function institutionTypes(): Collection
    {
        return DB::table('institution_types')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'code']);
    }

    public function render(): View
    {
        return view('livewire.admin.institutions.index', [
            'institutions' => $this->institutions(),
            'institutionTypes' => $this->institutionTypes(),
        ])->layout('layouts.admin');
    }
}
